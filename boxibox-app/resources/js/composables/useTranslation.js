import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Supported locales
export const LOCALES = {
    fr: { code: 'fr', name: 'Français', flag: '🇫🇷' },
    en: { code: 'en', name: 'English', flag: '🇬🇧' },
    es: { code: 'es', name: 'Español', flag: '🇪🇸' },
    nl: { code: 'nl', name: 'Nederlands', flag: '🇳🇱' },
}

// Current locale (reactive)
const currentLocale = ref(localStorage.getItem('locale') || 'fr')

// Translations cache
const translations = ref({})

// Load translations for a locale
async function loadTranslations(locale) {
    if (translations.value[locale]) {
        return translations.value[locale]
    }

    try {
        // Try the API route first (with caching)
        const response = await fetch(`/lang/${locale}`)
        if (response.ok) {
            const data = await response.json()
            translations.value[locale] = data
            return data
        }
    } catch (error) {
        console.error(`Failed to load translations for ${locale}:`, error)
    }

    return {}
}

// Initialize translations
loadTranslations(currentLocale.value)

export function useTranslation() {
    const page = usePage()

    // Get current locale from page props or localStorage
    const locale = computed(() => {
        return page.props?.locale || currentLocale.value
    })

    // Translate function
    const t = (key, replacements = {}) => {
        const keys = key.split('.')
        let value = translations.value[locale.value]

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k]
            } else {
                return key
            }
        }

        if (typeof value !== 'string') {
            return key
        }

        let result = value
        for (const [replaceKey, replaceValue] of Object.entries(replacements)) {
            result = result.replace(new RegExp(`\{${replaceKey}\}`, 'g'), replaceValue)
            result = result.replace(new RegExp(`:${replaceKey}`, 'g'), replaceValue)
        }

        return result
    }

    // Change locale
    const setLocale = async (newLocale) => {
        if (!LOCALES[newLocale]) {
            console.error(`Unsupported locale: ${newLocale}`)
            return
        }

        await loadTranslations(newLocale)
        localStorage.setItem('locale', newLocale)
        currentLocale.value = newLocale
    }

    const availableLocales = computed(() => Object.values(LOCALES))
    const currentLocaleInfo = computed(() => LOCALES[locale.value] || LOCALES.fr)

    return {
        t,
        locale,
        setLocale,
        availableLocales,
        currentLocaleInfo,
        LOCALES,
    }
}

export default useTranslation
