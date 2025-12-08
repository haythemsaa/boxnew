import { ref, computed, readonly } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

/**
 * Composable for managing tutorial/guide state across the application
 */

// Global state (singleton pattern)
const tutorialEnabled = ref(true)
const activeTourId = ref(null)
const completedTours = ref(new Set())
const currentPageTour = ref(null)

// Load saved preferences from localStorage
const loadPreferences = () => {
    try {
        const enabled = localStorage.getItem('tutorial_enabled')
        if (enabled !== null) {
            tutorialEnabled.value = enabled === 'true'
        }

        const completed = localStorage.getItem('tutorial_completed_tours')
        if (completed) {
            completedTours.value = new Set(JSON.parse(completed))
        }
    } catch (e) {
        console.warn('Failed to load tutorial preferences:', e)
    }
}

// Save preferences to localStorage
const savePreferences = () => {
    try {
        localStorage.setItem('tutorial_enabled', tutorialEnabled.value.toString())
        localStorage.setItem('tutorial_completed_tours', JSON.stringify([...completedTours.value]))
    } catch (e) {
        console.warn('Failed to save tutorial preferences:', e)
    }
}

// Initialize on first load
loadPreferences()

export function useTutorial() {
    const page = usePage()

    // Computed
    const isEnabled = computed(() => tutorialEnabled.value)
    const isFirstVisit = computed(() => {
        const user = page.props?.auth?.user
        if (!user) return false
        return !localStorage.getItem(`tutorial_user_${user.id}_welcomed`)
    })

    // Check if a specific tour was completed
    const isTourCompleted = (tourId) => {
        return completedTours.value.has(tourId) ||
               localStorage.getItem(`tutorial_${tourId}_completed`) === 'true'
    }

    // Get page-specific tour ID based on current route
    const getPageTourId = () => {
        const url = page.url || window.location.pathname

        // Map routes to tour IDs
        const routeTourMap = {
            '/dashboard': 'main',
            '/boxes': 'boxes',
            '/customers': 'customers',
            '/contracts': 'contracts',
            '/invoices': 'invoices',
            '/sites': 'sites',
            '/plan': 'plan',
            '/settings': 'settings',
        }

        for (const [route, tourId] of Object.entries(routeTourMap)) {
            if (url.includes(route)) {
                return tourId
            }
        }

        return 'main'
    }

    // Enable/disable tutorials globally
    const setEnabled = (enabled) => {
        tutorialEnabled.value = enabled
        savePreferences()

        // Also save to backend for persistence across devices
        const user = page.props?.auth?.user
        if (user) {
            router.post(route('tenant.user.preferences'), {
                key: 'tutorial_enabled',
                value: enabled
            }, { preserveScroll: true, preserveState: true })
        }
    }

    // Mark a tour as completed
    const markTourCompleted = (tourId) => {
        completedTours.value.add(tourId)
        savePreferences()
        localStorage.setItem(`tutorial_${tourId}_completed`, 'true')
    }

    // Reset all tutorials (for testing or user request)
    const resetTutorials = () => {
        completedTours.value.clear()
        savePreferences()

        // Clear all tutorial-related localStorage items
        const keysToRemove = []
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i)
            if (key && key.startsWith('tutorial_')) {
                keysToRemove.push(key)
            }
        }
        keysToRemove.forEach(key => localStorage.removeItem(key))
    }

    // Mark first visit as done
    const markWelcomed = () => {
        const user = page.props?.auth?.user
        if (user) {
            localStorage.setItem(`tutorial_user_${user.id}_welcomed`, 'true')
        }
    }

    // Start a specific tour programmatically
    const startTour = (tourId = 'main') => {
        activeTourId.value = tourId
        // The actual tour start is handled by the TutorialGuide component
        return tourId
    }

    // Should auto-start tour for current page?
    const shouldAutoStart = () => {
        if (!tutorialEnabled.value) return false

        const tourId = getPageTourId()
        if (isTourCompleted(tourId)) return false

        // Only auto-start main tour on first visit
        if (tourId === 'main' && isFirstVisit.value) {
            return true
        }

        return false
    }

    return {
        // State (read-only)
        isEnabled: readonly(isEnabled),
        isFirstVisit: readonly(isFirstVisit),
        activeTourId: readonly(activeTourId),

        // Methods
        setEnabled,
        isTourCompleted,
        markTourCompleted,
        resetTutorials,
        markWelcomed,
        startTour,
        shouldAutoStart,
        getPageTourId,
    }
}

// Export individual functions for direct use
export const enableTutorial = () => {
    tutorialEnabled.value = true
    savePreferences()
}

export const disableTutorial = () => {
    tutorialEnabled.value = false
    savePreferences()
}

export const toggleTutorial = () => {
    tutorialEnabled.value = !tutorialEnabled.value
    savePreferences()
    return tutorialEnabled.value
}
