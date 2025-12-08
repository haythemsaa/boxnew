import { ref } from 'vue'

/**
 * Composable for multi-step form validation
 * @param {Object} validationRules - Object with step numbers as keys and validation functions as values
 * @returns {Object} - Validation state and methods
 */
export function useStepValidation(validationRules = {}) {
    const stepErrors = ref({})

    /**
     * Validate a specific step
     * @param {number} step - Step number to validate
     * @param {Object} formData - Form data to validate
     * @returns {Object} - Errors object
     */
    const validateStep = (step, formData) => {
        const errors = {}

        if (validationRules[step]) {
            const stepRules = validationRules[step]

            for (const [field, rule] of Object.entries(stepRules)) {
                const value = formData[field]

                // Required validation
                if (rule.required && !value && value !== 0) {
                    errors[field] = rule.message || `Ce champ est obligatoire`
                    continue
                }

                // Min value validation
                if (rule.min !== undefined && value < rule.min) {
                    errors[field] = rule.minMessage || `La valeur minimum est ${rule.min}`
                    continue
                }

                // Max value validation
                if (rule.max !== undefined && value > rule.max) {
                    errors[field] = rule.maxMessage || `La valeur maximum est ${rule.max}`
                    continue
                }

                // Email validation
                if (rule.email && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                    if (!emailRegex.test(value)) {
                        errors[field] = rule.emailMessage || `Email invalide`
                        continue
                    }
                }

                // Phone validation
                if (rule.phone && value) {
                    const phoneRegex = /^[\d\s\+\-\(\)\.]{8,20}$/
                    if (!phoneRegex.test(value)) {
                        errors[field] = rule.phoneMessage || `Numéro de téléphone invalide`
                        continue
                    }
                }

                // Date comparison
                if (rule.afterDate && value && formData[rule.afterDate]) {
                    if (new Date(value) <= new Date(formData[rule.afterDate])) {
                        errors[field] = rule.afterDateMessage || `La date doit être après ${rule.afterDate}`
                        continue
                    }
                }

                // Custom validation function
                if (rule.custom && typeof rule.custom === 'function') {
                    const customError = rule.custom(value, formData)
                    if (customError) {
                        errors[field] = customError
                    }
                }
            }
        }

        return errors
    }

    /**
     * Check if current step can proceed
     * @param {number} step - Current step
     * @param {Object} formData - Form data
     * @returns {boolean}
     */
    const canProceed = (step, formData) => {
        const errors = validateStep(step, formData)
        return Object.keys(errors).length === 0
    }

    /**
     * Try to go to next step with validation
     * @param {number} currentStep - Current step
     * @param {number} totalSteps - Total number of steps
     * @param {Object} formData - Form data
     * @param {Function} setStep - Function to set the step
     * @returns {boolean} - Whether navigation was successful
     */
    const nextStep = (currentStep, totalSteps, formData, setStep) => {
        const errors = validateStep(currentStep, formData)
        stepErrors.value = errors

        if (Object.keys(errors).length > 0) {
            // Scroll to first error
            setTimeout(() => {
                const firstErrorField = document.querySelector('.field-error')
                if (firstErrorField) {
                    firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
                }
            }, 100)
            return false
        }

        if (currentStep < totalSteps) {
            setStep(currentStep + 1)
            stepErrors.value = {}
            return true
        }

        return false
    }

    /**
     * Go to previous step
     * @param {number} currentStep - Current step
     * @param {Function} setStep - Function to set the step
     */
    const prevStep = (currentStep, setStep) => {
        if (currentStep > 1) {
            setStep(currentStep - 1)
            stepErrors.value = {}
        }
    }

    /**
     * Validate all steps before final submission
     * @param {number} totalSteps - Total number of steps
     * @param {Object} formData - Form data
     * @returns {boolean} - Whether all validations passed
     */
    const validateAll = (totalSteps, formData) => {
        const allErrors = {}

        for (let i = 1; i <= totalSteps; i++) {
            Object.assign(allErrors, validateStep(i, formData))
        }

        stepErrors.value = allErrors
        return Object.keys(allErrors).length === 0
    }

    /**
     * Clear all errors
     */
    const clearErrors = () => {
        stepErrors.value = {}
    }

    /**
     * Check if a specific field has an error
     * @param {string} field - Field name
     * @returns {string|null} - Error message or null
     */
    const getError = (field) => {
        return stepErrors.value[field] || null
    }

    /**
     * Check if there are any errors
     * @returns {boolean}
     */
    const hasErrors = () => {
        return Object.keys(stepErrors.value).length > 0
    }

    return {
        stepErrors,
        validateStep,
        canProceed,
        nextStep,
        prevStep,
        validateAll,
        clearErrors,
        getError,
        hasErrors
    }
}

/**
 * Common validation rules for reuse
 */
export const commonRules = {
    required: (message) => ({ required: true, message }),
    email: (message) => ({ required: true, email: true, message, emailMessage: message || 'Email invalide' }),
    phone: (message) => ({ phone: true, phoneMessage: message || 'Numéro de téléphone invalide' }),
    minValue: (min, message) => ({ min, minMessage: message }),
    maxValue: (max, message) => ({ max, maxMessage: message }),
    afterDate: (dateField, message) => ({ afterDate: dateField, afterDateMessage: message }),
}
