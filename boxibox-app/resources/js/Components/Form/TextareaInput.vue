<template>
    <div class="form-group" :class="{ 'has-error': error, 'has-value': hasValue, 'is-focused': isFocused }">
        <!-- Label -->
        <label v-if="label" :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>

        <!-- Textarea wrapper -->
        <div class="textarea-wrapper">
            <textarea
                :id="id"
                ref="textareaRef"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :rows="rows"
                :maxlength="maxlength"
                class="form-textarea"
                :class="{ 'auto-resize': autoResize }"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
            ></textarea>

            <!-- Focus ring -->
            <div class="focus-ring"></div>
        </div>

        <!-- Footer -->
        <div class="textarea-footer">
            <!-- Error/Hint -->
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
                <span :class="{ 'text-red-500': charCount > maxlength, 'text-amber-500': charCount > maxlength * 0.9 }">
                    {{ charCount }}
                </span>
                / {{ maxlength }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: String,
    id: String,
    label: String,
    placeholder: String,
    error: String,
    hint: String,
    disabled: Boolean,
    readonly: Boolean,
    required: Boolean,
    rows: { type: Number, default: 4 },
    maxlength: Number,
    showCount: Boolean,
    autoResize: Boolean,
    autofocus: Boolean,
})

const emit = defineEmits(['update:modelValue', 'focus', 'blur'])

const textareaRef = ref(null)
const isFocused = ref(false)

const hasValue = computed(() => {
    return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

const charCount = computed(() => {
    return String(props.modelValue || '').length
})

const onInput = (e) => {
    emit('update:modelValue', e.target.value)
    if (props.autoResize) {
        resizeTextarea()
    }
}

const onFocus = (e) => {
    isFocused.value = true
    emit('focus', e)
}

const onBlur = (e) => {
    isFocused.value = false
    emit('blur', e)
}

const resizeTextarea = () => {
    if (!textareaRef.value) return
    textareaRef.value.style.height = 'auto'
    textareaRef.value.style.height = textareaRef.value.scrollHeight + 'px'
}

const focus = () => {
    textareaRef.value?.focus()
}

const blur = () => {
    textareaRef.value?.blur()
}

defineExpose({ focus, blur, textareaRef })

watch(() => props.modelValue, () => {
    if (props.autoResize) {
        nextTick(resizeTextarea)
    }
})

onMounted(() => {
    if (props.autofocus) {
        nextTick(() => textareaRef.value?.focus())
    }
    if (props.autoResize && props.modelValue) {
        nextTick(resizeTextarea)
    }
})
</script>

<style scoped>
@reference "tailwindcss";

.form-group {
    @apply relative;
}

.form-label {
    @apply block text-sm font-medium text-gray-700 mb-1.5;
}

.textarea-wrapper {
    @apply relative;
}

.form-textarea {
    @apply w-full bg-white border-2 border-gray-200 rounded-xl text-gray-900 placeholder-gray-400
           px-4 py-3 resize-y min-h-[100px]
           transition-all duration-200 ease-out
           focus:outline-none focus:border-blue-500;
}

.form-textarea.auto-resize {
    @apply resize-none overflow-hidden;
}

.form-textarea:disabled {
    @apply bg-gray-50 text-gray-500 cursor-not-allowed;
}

.form-textarea:hover:not(:disabled):not(:focus) {
    @apply border-gray-300;
}

/* Focus ring */
.focus-ring {
    @apply absolute inset-0 rounded-xl pointer-events-none
           transition-all duration-200 ease-out opacity-0;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.is-focused .focus-ring {
    @apply opacity-100;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

/* Error state */
.has-error .form-textarea {
    @apply border-red-400 focus:border-red-500;
}

.has-error .focus-ring {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.has-error.is-focused .focus-ring {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
}

/* Footer */
.textarea-footer {
    @apply flex items-start justify-between mt-1.5;
}

.error-message {
    @apply flex items-center text-sm text-red-600;
}

.hint-message {
    @apply text-sm text-gray-500;
}

.char-count {
    @apply text-xs text-gray-400 ml-auto;
}

/* Animation */
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
