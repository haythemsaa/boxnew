import { ref, watch, onMounted } from 'vue'

/**
 * Composable for managing dark mode across the application.
 *
 * Usage:
 * const { isDark, toggle, setMode } = useDarkMode()
 *
 * In templates:
 * <button @click="toggle">Toggle Dark Mode</button>
 * <span>{{ isDark ? 'Dark' : 'Light' }} mode</span>
 */
export function useDarkMode() {
    // State
    const isDark = ref(false)
    const mode = ref('system') // 'light', 'dark', or 'system'

    // Storage key
    const STORAGE_KEY = 'boxibox-dark-mode'

    /**
     * Apply the dark mode class to the document
     */
    const applyTheme = (dark) => {
        if (typeof document !== 'undefined') {
            if (dark) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        }
    }

    /**
     * Get system preference
     */
    const getSystemPreference = () => {
        if (typeof window !== 'undefined' && window.matchMedia) {
            return window.matchMedia('(prefers-color-scheme: dark)').matches
        }
        return false
    }

    /**
     * Calculate the effective dark mode based on current mode setting
     */
    const calculateEffectiveMode = (currentMode) => {
        if (currentMode === 'system') {
            return getSystemPreference()
        }
        return currentMode === 'dark'
    }

    /**
     * Set the mode ('light', 'dark', or 'system')
     */
    const setMode = (newMode) => {
        mode.value = newMode
        isDark.value = calculateEffectiveMode(newMode)
        applyTheme(isDark.value)

        // Persist to localStorage
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                mode: newMode,
                timestamp: Date.now()
            }))
        }
    }

    /**
     * Toggle between light and dark (not system)
     */
    const toggle = () => {
        const newMode = isDark.value ? 'light' : 'dark'
        setMode(newMode)
    }

    /**
     * Cycle through modes: light -> dark -> system -> light
     */
    const cycle = () => {
        const modes = ['light', 'dark', 'system']
        const currentIndex = modes.indexOf(mode.value)
        const nextIndex = (currentIndex + 1) % modes.length
        setMode(modes[nextIndex])
    }

    /**
     * Initialize dark mode from localStorage or system preference
     */
    const init = () => {
        if (typeof localStorage !== 'undefined') {
            try {
                const stored = localStorage.getItem(STORAGE_KEY)
                if (stored) {
                    const { mode: storedMode } = JSON.parse(stored)
                    if (['light', 'dark', 'system'].includes(storedMode)) {
                        mode.value = storedMode
                        isDark.value = calculateEffectiveMode(storedMode)
                        applyTheme(isDark.value)
                        return
                    }
                }
            } catch (e) {
                console.warn('Failed to parse dark mode preference:', e)
            }
        }

        // Default to system preference
        mode.value = 'system'
        isDark.value = getSystemPreference()
        applyTheme(isDark.value)
    }

    // Watch for system preference changes when in 'system' mode
    onMounted(() => {
        init()

        // Listen for system preference changes
        if (typeof window !== 'undefined' && window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
            const handleChange = (e) => {
                if (mode.value === 'system') {
                    isDark.value = e.matches
                    applyTheme(isDark.value)
                }
            }

            // Use addEventListener if available (modern browsers)
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleChange)
            } else {
                // Fallback for older browsers
                mediaQuery.addListener(handleChange)
            }
        }
    })

    return {
        // State
        isDark,
        mode,

        // Methods
        toggle,
        cycle,
        setMode,
        init,

        // Helpers
        isLight: () => !isDark.value,
        isSystem: () => mode.value === 'system',
    }
}

export default useDarkMode
