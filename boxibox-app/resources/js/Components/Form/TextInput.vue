<template>
    <div class="form-group" :class="{ 'has-error': error, 'has-value': hasValue, 'is-focused': isFocused }">
        <div class="input-wrapper">
            <!-- Icon prefix -->
            <div v-if="$slots.icon" class="input-icon">
                <slot name="icon" />
            </div>

            <!-- Input field -->
            <input
                :id="id"
                ref="inputRef"
                :type="type"
                :value="modelValue"
                :placeholder="floatingLabel ? ' ' : placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :autocomplete="autocomplete"
                :class="[
                    'form-input',
                    size,
                    { 'has-icon': $slots.icon, 'has-suffix': $slots.suffix }
                ]"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                @keydown="onKeydown"
            />

            <!-- Floating label -->
            <label
                v-if="floatingLabel && label"
                :for="id"
                class="floating-label"
                :class="{ 'has-icon': $slots.icon }"
            >
                {{ label }}
                <span v-if="required" class="text-red-500 ml-0.5">*</span>
            </label>

            <!-- Suffix slot (icons, buttons) -->
            <div v-if="$slots.suffix" class="input-suffix">
                <slot name="suffix" />
            </div>

            <!-- Clear button -->
            <button
                v-if="clearable && hasValue && !disabled"
                type="button"
                class="clear-button"
                @click="clearInput"
            >
                <XMarkIcon class="w-4 h-4" />
            </button>

            <!-- Focus ring animation -->
            <div class="focus-ring"></div>
        </div>

        <!-- Static label (non-floating) -->
        <label v-if="!floatingLabel && label" :for="id" class="static-label">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>

        <!-- Helper text / Error -->
        <transition name="slide-fade">
            <p v-if="error" class="error-message">
                <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                {{ error }}
            </p>
            <p v-else-if="hint" class="hint-message">
                {{ hint }}
            </p>
        </transition>

        <!-- Character count -->
        <div v-if="showCount && maxlength" class="char-count">
            <span :class="{ 'text-red-500': charCount > maxlength }">{{ charCount }}</span>
            / {{ maxlength }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { XMarkIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: [String, Number],
    id: String,
    type: { type: String, default: 'text' },
    label: String,
    placeholder: String,
    error: String,
    hint: String,
    disabled: Boolean,
    readonly: Boolean,
    required: Boolean,
    autocomplete: String,
    floatingLabel: { type: Boolean, default: true },
    clearable: Boolean,
    size: { type: String, default: 'md' }, // sm, md, lg
    maxlength: Number,
    showCount: Boolean,
    autofocus: Boolean,
})

const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'clear', 'keydown'])

const inputRef = ref(null)
const isFocused = ref(false)

const hasValue = computed(() => {
    return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

const charCount = computed(() => {
    return String(props.modelValue || '').length
})

const onInput = (e) => {
    emit('update:modelValue', e.target.value)
}

const onFocus = (e) => {
    isFocused.value = true
    emit('focus', e)
}

const onBlur = (e) => {
    isFocused.value = false
    emit('blur', e)
}

const onKeydown = (e) => {
    emit('keydown', e)
}

const clearInput = () => {
    emit('update:modelValue', '')
    emit('clear')
    nextTick(() => {
        inputRef.value?.focus()
    })
}

const focus = () => {
    inputRef.value?.focus()
}

const blur = () => {
    inputRef.value?.blur()
}

defineExpose({ focus, blur, inputRef })

onMounted(() => {
    if (props.autofocus) {
        nextTick(() => {
            inputRef.value?.focus()
        })
    }
})
</script>

<style scoped>
@reference "tailwindcss";

.form-group {
    @apply relative;
}

.static-label {
    @apply block text-sm font-medium text-gray-700 mb-1.5;
}

.input-wrapper {
    @apply relative;
}

.form-input {
    @apply w-full bg-white border-2 border-gray-200 rounded-xl text-gray-900 placeholder-gray-400
           transition-all duration-300 ease-out
           focus:outline-none focus:border-blue-500 focus:ring-0;
}

.form-input.sm {
    @apply px-3 py-2 text-sm;
}

.form-input.md {
    @apply px-4 py-3 text-base;
}

.form-input.lg {
    @apply px-5 py-4 text-lg;
}

.form-input.has-icon {
    @apply pl-11;
}

.form-input.has-icon.sm {
    @apply pl-9;
}

.form-input.has-icon.lg {
    @apply pl-14;
}

.form-input.has-suffix {
    @apply pr-11;
}

.form-input:disabled {
    @apply bg-gray-50 text-gray-500 cursor-not-allowed border-gray-200;
}

.form-input:hover:not(:disabled):not(:focus) {
    @apply border-gray-300;
}

/* Icon styles */
.input-icon {
    @apply absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 transition-colors duration-200 pointer-events-none;
}

.sm .input-icon {
    @apply left-3;
}

.lg .input-icon {
    @apply left-4;
}

.is-focused .input-icon,
.has-value .input-icon {
    @apply text-blue-500;
}

.has-error .input-icon {
    @apply text-red-500;
}

/* Floating label */
.floating-label {
    @apply absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 pointer-events-none
           transition-all duration-200 ease-out origin-left bg-white px-1;
}

.floating-label.has-icon {
    @apply left-11;
}

.is-focused .floating-label,
.has-value .floating-label {
    @apply -top-0 text-xs font-medium scale-90 text-blue-600;
}

.has-error .floating-label {
    @apply text-red-600;
}

/* Suffix */
.input-suffix {
    @apply absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400;
}

/* Clear button */
.clear-button {
    @apply absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-full text-gray-400
           hover:text-gray-600 hover:bg-gray-100 transition-all duration-200
           opacity-0 scale-75;
}

.is-focused .clear-button,
.has-value:hover .clear-button {
    @apply opacity-100 scale-100;
}

/* Focus ring animation */
.focus-ring {
    @apply absolute inset-0 rounded-xl pointer-events-none
           transition-all duration-300 ease-out opacity-0;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.is-focused .focus-ring {
    @apply opacity-100;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

/* Error state */
.has-error .form-input {
    @apply border-red-400 focus:border-red-500;
}

.has-error .focus-ring {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.has-error.is-focused .focus-ring {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
}

/* Messages */
.error-message {
    @apply flex items-center mt-1.5 text-sm text-red-600;
}

.hint-message {
    @apply mt-1.5 text-sm text-gray-500;
}

/* Character count */
.char-count {
    @apply absolute right-0 -bottom-5 text-xs text-gray-400;
}

/* Slide fade animation */
.slide-fade-enter-active {
    transition: all 0.2s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.15s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}
</style>
