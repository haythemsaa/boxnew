import { ref, computed, reactive } from 'vue'

export const useDynamicForm = (initialFields = [], initialData = {}) => {
    const fields = ref(initialFields)
    const formData = reactive({ ...initialData })
    const errors = reactive({})
    const loading = ref(false)
    const submitted = ref(false)

    // Update form field
    const setFieldValue = (fieldName, value) => {
        formData[fieldName] = value
        // Clear error for this field when user starts typing
        if (errors[fieldName]) {
            delete errors[fieldName]
        }
    }

    // Set multiple field values
    const setFieldValues = (data) => {
        Object.assign(formData, data)
    }

    // Set field error
    const setFieldError = (fieldName, error) => {
        if (error) {
            errors[fieldName] = error
        } else {
            delete errors[fieldName]
        }
    }

    // Set multiple errors
    const setErrors = (fieldErrors) => {
        Object.assign(errors, fieldErrors)
    }

    // Clear errors
    const clearErrors = () => {
        Object.keys(errors).forEach((key) => {
            delete errors[key]
        })
    }

    // Reset form
    const resetForm = () => {
        Object.keys(formData).forEach((key) => {
            formData[key] = ''
        })
        clearErrors()
        submitted.value = false
    }

    // Add a new field dynamically
    const addField = (field) => {
        fields.value.push(field)
        formData[field.name] = field.defaultValue || ''
    }

    // Remove a field
    const removeField = (fieldName) => {
        fields.value = fields.value.filter((f) => f.name !== fieldName)
        delete formData[fieldName]
        delete errors[fieldName]
    }

    // Show/hide field conditionally
    const toggleField = (fieldName, visible) => {
        const field = fields.value.find((f) => f.name === fieldName)
        if (field) {
            field.hidden = !visible
        }
    }

    // Disable/enable field
    const setFieldDisabled = (fieldName, disabled) => {
        const field = fields.value.find((f) => f.name === fieldName)
        if (field) {
            field.disabled = disabled
        }
    }

    // Validate single field
    const validateField = (fieldName) => {
        const field = fields.value.find((f) => f.name === fieldName)
        if (!field) return true

        const value = formData[fieldName]
        let error = null

        // Check required
        if (field.required && !value) {
            error = `${field.label} est obligatoire`
        }

        // Check email
        if (!error && field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            if (!emailRegex.test(value)) {
                error = 'Adresse e-mail invalide'
            }
        }

        // Check min length
        if (!error && field.minLength && value && value.length < field.minLength) {
            error = `Au minimum ${field.minLength} caractères requis`
        }

        // Check max length
        if (!error && field.maxLength && value && value.length > field.maxLength) {
            error = `Maximum ${field.maxLength} caractères autorisé`
        }

        // Custom validation
        if (!error && field.validate && typeof field.validate === 'function') {
            error = field.validate(value)
        }

        if (error) {
            errors[fieldName] = error
        } else {
            delete errors[fieldName]
        }

        return !error
    }

    // Validate all fields
    const validateForm = () => {
        let isValid = true
        fields.value.forEach((field) => {
            if (!validateField(field.name)) {
                isValid = false
            }
        })
        submitted.value = true
        return isValid
    }

    // Get form state
    const getFormData = () => ({ ...formData })

    // Check if form is valid
    const isValid = computed(() => Object.keys(errors).length === 0)

    // Check if form is dirty
    const isDirty = computed(() => {
        return Object.keys(formData).some((key) => formData[key] !== initialData[key])
    })

    return {
        fields,
        formData,
        errors,
        loading,
        submitted,
        setFieldValue,
        setFieldValues,
        setFieldError,
        setErrors,
        clearErrors,
        resetForm,
        addField,
        removeField,
        toggleField,
        setFieldDisabled,
        validateField,
        validateForm,
        getFormData,
        isValid,
        isDirty,
    }
}
