<template>
    <div class="form-group" :class="{ 'has-error': error, 'has-value': hasValue, 'is-open': isOpen }">
        <!-- Label -->
        <label v-if="label" :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>

        <!-- Select wrapper -->
        <div class="select-wrapper" ref="selectRef">
            <button
                type="button"
                :id="id"
                :disabled="disabled"
                :aria-expanded="isOpen"
                :aria-haspopup="'listbox'"
                :aria-controls="dropdownId"
                :aria-invalid="!!error"
                :aria-describedby="error ? errorId : null"
                :aria-required="required"
                class="select-trigger"
                :class="[size, { 'has-icon': $slots.icon }]"
                @click="toggleDropdown"
                @keydown="handleKeydown"
            >
                <!-- Icon -->
                <span v-if="$slots.icon" class="select-icon">
                    <slot name="icon" />
                </span>

                <!-- Selected value display -->
                <span class="select-value" :class="{ 'text-gray-400': !hasValue }">
                    {{ displayValue }}
                </span>

                <!-- Dropdown arrow -->
                <span class="select-arrow">
                    <ChevronDownIcon class="w-5 h-5 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
                </span>
            </button>

            <!-- Dropdown -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 -translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100 translate-y-0"
                leave-to-class="opacity-0 scale-95 -translate-y-2"
            >
                <div
                    v-show="isOpen"
                    :id="dropdownId"
                    role="listbox"
                    :aria-label="label || 'Options'"
                    class="select-dropdown"
                    :class="dropdownPosition"
                >
                    <!-- Search -->
                    <div v-if="searchable" class="dropdown-search">
                        <MagnifyingGlassIcon class="w-4 h-4 text-gray-400" aria-hidden="true" />
                        <input
                            ref="searchInputRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher..."
                            aria-label="Rechercher dans les options"
                            class="search-input"
                            @click.stop
                        />
                    </div>

                    <!-- Options -->
                    <div class="dropdown-options" ref="optionsRef" aria-live="polite">
                        <div
                            v-if="filteredOptions.length === 0"
                            class="no-options"
                            role="status"
                        >
                            Aucun resultat
                        </div>

                        <button
                            v-for="(option, index) in filteredOptions"
                            :key="getOptionValue(option)"
                            type="button"
                            role="option"
                            :aria-selected="isSelected(option)"
                            class="dropdown-option"
                            :class="{
                                'is-selected': isSelected(option),
                                'is-highlighted': highlightedIndex === index
                            }"
                            @click="selectOption(option)"
                            @mouseenter="highlightedIndex = index"
                        >
                            <span class="option-label">{{ getOptionLabel(option) }}</span>
                            <CheckIcon v-if="isSelected(option)" class="w-4 h-4 text-primary-600" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Error message -->
        <transition name="slide-fade">
            <p v-if="error" :id="errorId" class="error-message" role="alert" aria-live="assertive">
                <ExclamationCircleIcon class="w-4 h-4 mr-1" aria-hidden="true" />
                {{ error }}
            </p>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import {
    ChevronDownIcon,
    CheckIcon,
    MagnifyingGlassIcon,
    ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: [String, Number, Object],
    options: { type: Array, default: () => [] },
    id: String,
    label: String,
    placeholder: { type: String, default: 'Selectionner...' },
    error: String,
    disabled: Boolean,
    required: Boolean,
    searchable: Boolean,
    size: { type: String, default: 'md' },
    labelKey: { type: String, default: 'label' },
    valueKey: { type: String, default: 'value' },
    returnObject: Boolean,
})

const emit = defineEmits(['update:modelValue', 'change'])

const selectRef = ref(null)
const searchInputRef = ref(null)
const optionsRef = ref(null)
const isOpen = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(0)
const dropdownPosition = ref('bottom')

// Generate unique IDs for accessibility
const uniqueId = Math.random().toString(36).substr(2, 9)
const dropdownId = computed(() => `dropdown-${props.id || uniqueId}`)
const errorId = computed(() => `error-${props.id || uniqueId}`)

const hasValue = computed(() => {
    return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

const displayValue = computed(() => {
    if (!hasValue.value) return props.placeholder

    const selected = props.options.find(opt => {
        const optValue = getOptionValue(opt)
        const currentValue = props.returnObject ? getOptionValue(props.modelValue) : props.modelValue
        return optValue === currentValue
    })

    return selected ? getOptionLabel(selected) : props.placeholder
})

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options

    const query = searchQuery.value.toLowerCase()
    return props.options.filter(opt => {
        const label = getOptionLabel(opt).toLowerCase()
        return label.includes(query)
    })
})

const getOptionLabel = (option) => {
    if (typeof option === 'object' && option !== null) {
        return option[props.labelKey] || option.name || option.title || String(option)
    }
    return String(option)
}

const getOptionValue = (option) => {
    if (typeof option === 'object' && option !== null) {
        return option[props.valueKey] || option.id || option
    }
    return option
}

const isSelected = (option) => {
    const optValue = getOptionValue(option)
    const currentValue = props.returnObject ? getOptionValue(props.modelValue) : props.modelValue
    return optValue === currentValue
}

const toggleDropdown = () => {
    if (props.disabled) return
    isOpen.value = !isOpen.value

    if (isOpen.value) {
        nextTick(() => {
            calculatePosition()
            if (props.searchable && searchInputRef.value) {
                searchInputRef.value.focus()
            }
            // Scroll to selected option
            scrollToSelected()
        })
    }
}

const calculatePosition = () => {
    if (!selectRef.value) return

    const rect = selectRef.value.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom
    const spaceAbove = rect.top

    dropdownPosition.value = spaceBelow < 250 && spaceAbove > spaceBelow ? 'top' : 'bottom'
}

const selectOption = (option) => {
    const value = props.returnObject ? option : getOptionValue(option)
    emit('update:modelValue', value)
    emit('change', value)
    isOpen.value = false
    searchQuery.value = ''
}

const scrollToSelected = () => {
    nextTick(() => {
        const selectedEl = optionsRef.value?.querySelector('.is-selected')
        if (selectedEl) {
            selectedEl.scrollIntoView({ block: 'nearest' })
        }
    })
}

const handleKeydown = (e) => {
    if (!isOpen.value) {
        if (['Enter', 'Space', 'ArrowDown', 'ArrowUp'].includes(e.code)) {
            e.preventDefault()
            isOpen.value = true
        }
        return
    }

    switch (e.code) {
        case 'ArrowDown':
            e.preventDefault()
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1)
            scrollToHighlighted()
            break
        case 'ArrowUp':
            e.preventDefault()
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0)
            scrollToHighlighted()
            break
        case 'Enter':
        case 'Space':
            e.preventDefault()
            if (filteredOptions.value[highlightedIndex.value]) {
                selectOption(filteredOptions.value[highlightedIndex.value])
            }
            break
        case 'Escape':
            e.preventDefault()
            isOpen.value = false
            break
    }
}

const scrollToHighlighted = () => {
    nextTick(() => {
        const highlightedEl = optionsRef.value?.querySelector('.is-highlighted')
        if (highlightedEl) {
            highlightedEl.scrollIntoView({ block: 'nearest' })
        }
    })
}

const handleClickOutside = (e) => {
    if (selectRef.value && !selectRef.value.contains(e.target)) {
        isOpen.value = false
        searchQuery.value = ''
    }
}

watch(searchQuery, () => {
    highlightedIndex.value = 0
})

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
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

.select-wrapper {
    @apply relative;
}

.select-trigger {
    @apply w-full flex items-center bg-white border-2 border-gray-200 rounded-xl text-left
           transition-all duration-200 ease-out cursor-pointer
           hover:border-gray-300 focus:outline-none focus:border-blue-500;
}

.select-trigger.sm {
    @apply px-3 py-2 text-sm;
}

.select-trigger.md {
    @apply px-4 py-3 text-base;
}

.select-trigger.lg {
    @apply px-5 py-4 text-lg;
}

.select-trigger.has-icon {
    @apply pl-11;
}

.select-trigger:disabled {
    @apply bg-gray-50 text-gray-500 cursor-not-allowed;
}

.is-open .select-trigger {
    @apply border-blue-500;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.select-icon {
    @apply absolute left-3.5 text-gray-400;
}

.is-open .select-icon,
.has-value .select-icon {
    @apply text-blue-500;
}

.select-value {
    @apply flex-1 truncate text-gray-900;
}

.select-arrow {
    @apply ml-2 text-gray-400;
}

.is-open .select-arrow {
    @apply text-blue-500;
}

/* Dropdown */
.select-dropdown {
    @apply absolute left-0 right-0 z-50 mt-2 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden;
    max-height: 280px;
}

.select-dropdown.top {
    @apply bottom-full top-auto mb-2 mt-0;
}

.dropdown-search {
    @apply flex items-center px-3 py-2 border-b border-gray-100;
}

.search-input {
    @apply flex-1 ml-2 bg-transparent border-none text-sm text-gray-900 placeholder-gray-400 focus:outline-none;
}

.dropdown-options {
    @apply overflow-y-auto;
    max-height: 220px;
}

.dropdown-option {
    @apply w-full flex items-center justify-between px-4 py-2.5 text-left text-gray-700
           transition-colors duration-100 cursor-pointer;
}

.dropdown-option:hover,
.dropdown-option.is-highlighted {
    @apply bg-gray-50;
}

.dropdown-option.is-selected {
    @apply bg-blue-50 text-blue-700 font-medium;
}

.option-label {
    @apply flex-1 truncate;
}

.no-options {
    @apply px-4 py-6 text-center text-gray-500 text-sm;
}

/* Error state */
.has-error .select-trigger {
    @apply border-red-400 focus:border-red-500;
}

.has-error.is-open .select-trigger {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.error-message {
    @apply flex items-center mt-1.5 text-sm text-red-600;
}

/* Animations */
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
