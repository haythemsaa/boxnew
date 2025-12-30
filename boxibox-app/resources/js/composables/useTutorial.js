import { ref, computed, readonly } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

/**
 * Composable for managing tutorial/guide state across the application
 *
 * Le guide interactif s'affiche UNIQUEMENT:
 * 1. La première fois que l'utilisateur se connecte (première visite)
 * 2. Quand l'utilisateur clique explicitement sur le bouton d'aide
 */

// Global state (singleton pattern)
const tutorialEnabled = ref(true)
const activeTourId = ref(null)
const completedTours = ref(new Set())
const currentPageTour = ref(null)
const hasShownWelcome = ref(false)

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

        // Vérifier si le tutoriel de bienvenue a déjà été montré
        const welcomed = localStorage.getItem('tutorial_welcome_shown')
        if (welcomed === 'true') {
            hasShownWelcome.value = true
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

    // Vérifie si c'est vraiment la première visite de l'utilisateur
    const isFirstVisit = computed(() => {
        const user = page.props?.auth?.user
        if (!user) return false

        // Vérifier TOUS les indicateurs pour s'assurer que c'est vraiment la première fois
        const userWelcomed = localStorage.getItem(`tutorial_user_${user.id}_welcomed`)
        const welcomeShown = localStorage.getItem('tutorial_welcome_shown')
        const mainCompleted = localStorage.getItem('tutorial_main_completed')

        // C'est la première visite SEULEMENT si aucun de ces indicateurs n'existe
        return !userWelcomed && !welcomeShown && !mainCompleted
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

    // Mark first visit as done - appelé après avoir montré/fermé le guide
    const markWelcomed = () => {
        const user = page.props?.auth?.user
        if (user) {
            localStorage.setItem(`tutorial_user_${user.id}_welcomed`, 'true')
        }
        // Marquer aussi globalement que le tutoriel a été montré
        localStorage.setItem('tutorial_welcome_shown', 'true')
        hasShownWelcome.value = true
    }

    // Start a specific tour programmatically (uniquement par clic utilisateur)
    const startTour = (tourId = 'main') => {
        activeTourId.value = tourId
        // The actual tour start is handled by the TutorialGuide component
        return tourId
    }

    /**
     * Détermine si le guide doit démarrer automatiquement
     *
     * RÈGLE: Le guide s'affiche automatiquement UNIQUEMENT:
     * - La première fois que l'utilisateur se connecte
     * - ET si les tutoriels sont activés globalement
     * - ET si ce tour spécifique n'a pas déjà été complété
     *
     * Après la première fois, l'utilisateur doit cliquer sur "Aide" pour relancer
     */
    const shouldAutoStart = () => {
        // Si les tutoriels sont désactivés, ne jamais auto-démarrer
        if (!tutorialEnabled.value) return false

        // Si le tutoriel de bienvenue a déjà été montré, ne pas auto-démarrer
        if (hasShownWelcome.value) return false

        const tourId = getPageTourId()

        // Si ce tour est déjà complété, ne pas auto-démarrer
        if (isTourCompleted(tourId)) return false

        // Auto-démarrer SEULEMENT si c'est la première visite ET c'est le tour principal
        if (tourId === 'main' && isFirstVisit.value) {
            return true
        }

        // Dans tous les autres cas, ne pas auto-démarrer
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
