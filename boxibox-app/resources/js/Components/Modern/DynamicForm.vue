<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Groupe de champs dynamiques -->
        <div
            v-for="(field, index) in fields"
            :key="field.name"
            class="animate-fade-in"
            :style="{ animationDelay: `${index * 50}ms` }"
        >
            <!-- Champ texte -->
            <AnimatedInput
                v-if="field.type === 'text' || field.type === 'email' || field.type === 'number'"
                :id="field.name"
                :type="field.type"
                :label="field.label"
                :model-value="formData[field.name]"
                :error="errors[field.name]"
                :hint="field.hint"
                :disabled="field.disabled"
                @update:model-value="(value) => updateField(field.name, value)"
            />

            <!-- Select -->
            <div v-else-if="field.type === 'select'" class="relative">
                <select
                    :id="field.name"
                    :value="formData[field.name]"
                    @change="(e) => updateField(field.name, e.target.value)"
                    :disabled="field.disabled"
                    class="peer w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-primary-500 focus:outline-none transition-colors duration-200 bg-white"
                    :class="{ 'border-red-500': errors[field.name] }"
                >
                    <option value="">{{ field.label }}</option>
                    <option v-for="option in field.options" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <label :for="field.name" class="absolute left-4 -top-2.5 bg-white px-2 text-xs font-semibold text-primary-500">
                    {{ field.label }}
                </label>
                <div v-if="errors[field.name]" class="mt-2 text-sm text-red-500">{{ errors[field.name] }}</div>
            </div>

            <!-- Textarea -->
            <div v-else-if="field.type === 'textarea'" class="relative">
                <textarea
                    :id="field.name"
                    :value="formData[field.name]"
                    @input="(e) => updateField(field.name, e.target.value)"
                    :placeholder="field.placeholder"
                    :rows="field.rows || 4"
                    :disabled="field.disabled"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-primary-500 focus:outline-none transition-colors duration-200 resize-none"
                    :class="{ 'border-red-500': errors[field.name] }"
                ></textarea>
                <label :for="field.name" class="absolute -top-2.5 left-4 bg-white px-2 text-xs font-semibold text-primary-500">
                    {{ field.label }}
                </label>
                <div v-if="errors[field.name]" class="mt-2 text-sm text-red-500">{{ errors[field.name] }}</div>
            </div>

            <!-- Checkbox -->
            <div v-else-if="field.type === 'checkbox'" class="flex items-center">
                <input
                    :id="field.name"
                    type="checkbox"
                    :checked="formData[field.name]"
                    @change="(e) => updateField(field.name, e.target.checked)"
                    :disabled="field.disabled"
                    class="w-5 h-5 text-primary-500 rounded focus:ring-2 focus:ring-primary-500 cursor-pointer"
                />
                <label :for="field.name" class="ml-3 font-medium text-gray-700 cursor-pointer">
                    {{ field.label }}
                </label>
            </div>

            <!-- Radio buttons -->
            <div v-else-if="field.type === 'radio'" class="space-y-3">
                <label class="block font-medium text-gray-700">{{ field.label }}</label>
                <div class="flex gap-4">
                    <div v-for="option in field.options" :key="option.value" class="flex items-center">
                        <input
                            :id="`${field.name}-${option.value}`"
                            type="radio"
                            :name="field.name"
                            :value="option.value"
                            :checked="formData[field.name] === option.value"
                            @change="(e) => updateField(field.name, e.target.value)"
                            :disabled="field.disabled"
                            class="w-4 h-4 text-primary-500 focus:ring-2 focus:ring-primary-500 cursor-pointer"
                        />
                        <label :for="`${field.name}-${option.value}`" class="ml-2 text-gray-700 cursor-pointer">
                            {{ option.label }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Date -->
            <div v-else-if="field.type === 'date'" class="relative">
                <input
                    :id="field.name"
                    type="date"
                    :value="formData[field.name]"
                    @change="(e) => updateField(field.name, e.target.value)"
                    :disabled="field.disabled"
                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-primary-500 focus:outline-none transition-colors duration-200"
                    :class="{ 'border-red-500': errors[field.name] }"
                />
                <label :for="field.name" class="absolute -top-2.5 left-4 bg-white px-2 text-xs font-semibold text-primary-500">
                    {{ field.label }}
                </label>
            </div>
        </div>

        <!-- Boutons d'action -->
        <div class="flex gap-4 pt-4 border-t border-gray-100">
            <ModernButton
                type="submit"
                label="Enregistrer"
                variant="primary"
                size="lg"
                :loading="loading"
                :disabled="disabled"
            />
            <ModernButton
                type="button"
                label="Annuler"
                variant="secondary"
                size="lg"
                @click="$emit('cancel')"
            />
        </div>
    </form>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import AnimatedInput from './AnimatedInput.vue'
import ModernButton from './ModernButton.vue'

const props = defineProps({
    fields: {
        type: Array,
        required: true,
    },
    initialData: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['submit', 'cancel', 'update:modelValue'])

const formData = reactive({ ...props.initialData })
const errors = reactive({})

const updateField = (name, value) => {
    formData[name] = value
    if (errors[name]) {
        delete errors[name]
    }
    emit('update:modelValue', formData)
}

const handleSubmit = () => {
    emit('submit', { ...formData })
}

watch(
    () => props.initialData,
    (newData) => {
        Object.assign(formData, newData)
    },
    { deep: true }
)
</script>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
    opacity: 0;
}
</style>
