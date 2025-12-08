<template>
    <Teleport to="body">
        <!-- Overlay -->
        <Transition name="fade">
            <div
                v-if="isActive && currentStep"
                class="fixed inset-0 z-[9998]"
                @click="handleOverlayClick"
            >
                <!-- Backdrop with spotlight effect -->
                <div class="absolute inset-0 bg-black/60 transition-opacity duration-300"></div>

                <!-- Spotlight hole (positioned dynamically) -->
                <div
                    v-if="spotlightStyle"
                    class="absolute bg-transparent rounded-lg shadow-[0_0_0_9999px_rgba(0,0,0,0.6)] transition-all duration-300 pointer-events-none"
                    :style="spotlightStyle"
                ></div>
            </div>
        </Transition>

        <!-- Tooltip/Card -->
        <Transition name="slide-fade">
            <div
                v-if="isActive && currentStep"
                ref="tooltipRef"
                class="fixed z-[9999] w-[360px] max-w-[90vw] bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden"
                :style="tooltipStyle"
            >
                <!-- Header with progress -->
                <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-5 py-4 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <AcademicCapIcon class="w-5 h-5" />
                            <span class="font-semibold text-sm">Guide interactif</span>
                        </div>
                        <button
                            @click="close"
                            class="p-1 hover:bg-white/20 rounded-full transition"
                            title="Fermer le guide"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Progress bar -->
                    <div class="flex items-center space-x-2">
                        <div class="flex-1 h-1.5 bg-white/30 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-white rounded-full transition-all duration-500"
                                :style="{ width: `${progressPercentage}%` }"
                            ></div>
                        </div>
                        <span class="text-xs font-medium">{{ currentStepIndex + 1 }}/{{ steps.length }}</span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-5">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ currentStep.title }}</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-4">{{ currentStep.description }}</p>

                    <!-- Tips section -->
                    <div v-if="currentStep.tips" class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                        <div class="flex items-start space-x-2">
                            <LightBulbIcon class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                            <p class="text-amber-800 text-xs">{{ currentStep.tips }}</p>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center justify-between">
                        <button
                            v-if="currentStepIndex > 0"
                            @click="previousStep"
                            class="flex items-center text-gray-600 hover:text-gray-900 text-sm font-medium transition"
                        >
                            <ChevronLeftIcon class="w-4 h-4 mr-1" />
                            Precedent
                        </button>
                        <div v-else></div>

                        <div class="flex items-center space-x-2">
                            <button
                                @click="skipTour"
                                class="px-3 py-1.5 text-gray-500 hover:text-gray-700 text-sm transition"
                            >
                                Passer
                            </button>
                            <button
                                @click="nextStep"
                                class="flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition shadow-sm"
                            >
                                {{ isLastStep ? 'Terminer' : 'Suivant' }}
                                <ChevronRightIcon v-if="!isLastStep" class="w-4 h-4 ml-1" />
                                <CheckIcon v-else class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Don't show again checkbox -->
                <div class="px-5 pb-4 pt-0">
                    <label class="flex items-center space-x-2 cursor-pointer group">
                        <input
                            type="checkbox"
                            v-model="dontShowAgain"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                        <span class="text-xs text-gray-500 group-hover:text-gray-700">Ne plus afficher automatiquement</span>
                    </label>
                </div>

                <!-- Arrow indicator -->
                <div
                    v-if="arrowDirection"
                    class="absolute w-4 h-4 bg-white transform rotate-45 border border-gray-100"
                    :class="arrowClasses"
                ></div>
            </div>
        </Transition>

        <!-- Floating help button -->
        <Transition name="bounce">
            <button
                v-if="!isActive && showFloatingButton"
                @click="startTour"
                class="fixed bottom-6 right-6 z-[9990] flex items-center space-x-2 px-4 py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200"
            >
                <QuestionMarkCircleIcon class="w-5 h-5" />
                <span class="font-medium text-sm">Aide</span>
            </button>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import {
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    CheckIcon,
    AcademicCapIcon,
    LightBulbIcon,
    QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tourId: {
        type: String,
        default: 'main'
    },
    autoStart: {
        type: Boolean,
        default: false
    },
    showFloatingButton: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['start', 'complete', 'skip', 'step-change'])

// State
const isActive = ref(false)
const currentStepIndex = ref(0)
const dontShowAgain = ref(false)
const tooltipRef = ref(null)
const spotlightStyle = ref(null)
const tooltipStyle = ref(null)
const arrowDirection = ref(null)

// Tour steps configuration - organized by page/section
const tourConfigs = {
    main: [
        {
            id: 'welcome',
            title: 'Bienvenue sur Boxibox!',
            description: 'Decouvrez comment gerer efficacement vos boxes de self-stockage. Ce guide vous accompagnera dans la prise en main de l\'application.',
            tips: 'Vous pouvez relancer ce guide a tout moment depuis le menu Aide.',
            target: null, // No target = centered modal
        },
        {
            id: 'sidebar',
            title: 'Menu de navigation',
            description: 'Utilisez ce menu pour acceder a toutes les sections: Dashboard, Clients, Boxes, Contrats, Factures et bien plus.',
            target: '[data-tutorial="sidebar"]',
            position: 'right',
        },
        {
            id: 'dashboard',
            title: 'Tableau de bord',
            description: 'Votre tableau de bord affiche les indicateurs cles: taux d\'occupation, revenus, alertes et activite recente.',
            target: '[data-tutorial="dashboard-stats"]',
            position: 'bottom',
            tips: 'Cliquez sur les cartes pour acceder aux details.',
        },
        {
            id: 'quick-actions',
            title: 'Actions rapides',
            description: 'Creez rapidement un nouveau client, contrat ou facture depuis ces boutons d\'action rapide.',
            target: '[data-tutorial="quick-actions"]',
            position: 'bottom',
        },
        {
            id: 'sites',
            title: 'Gestion des sites',
            description: 'Gerez vos differents sites de stockage. Chaque site peut avoir ses propres boxes, tarifs et parametres.',
            target: '[data-tutorial="sites-menu"]',
            position: 'right',
        },
        {
            id: 'boxes',
            title: 'Gestion des boxes',
            description: 'Consultez l\'etat de vos boxes: disponibles, loues, reserves ou en maintenance. Utilisez le plan interactif pour une vue globale.',
            target: '[data-tutorial="boxes-menu"]',
            position: 'right',
            tips: 'Le code couleur indique l\'etat de chaque box.',
        },
        {
            id: 'customers',
            title: 'Gestion des clients',
            description: 'Ajoutez et gerez vos clients, consultez leur historique de location et leurs documents.',
            target: '[data-tutorial="customers-menu"]',
            position: 'right',
        },
        {
            id: 'contracts',
            title: 'Gestion des contrats',
            description: 'Creez des contrats de location, gerez les renouvellements et suivez les echeances.',
            target: '[data-tutorial="contracts-menu"]',
            position: 'right',
        },
        {
            id: 'invoicing',
            title: 'Facturation',
            description: 'Generez des factures automatiques, suivez les paiements et gerez les relances.',
            target: '[data-tutorial="invoices-menu"]',
            position: 'right',
            tips: 'Activez la facturation automatique dans les parametres.',
        },
        {
            id: 'notifications',
            title: 'Notifications',
            description: 'Restez informe des evenements importants: paiements, fins de contrat, alertes de maintenance.',
            target: '[data-tutorial="notifications"]',
            position: 'bottom',
        },
        {
            id: 'settings',
            title: 'Parametres',
            description: 'Personnalisez votre espace: informations de l\'entreprise, preferences de facturation, integrations et plus.',
            target: '[data-tutorial="settings-menu"]',
            position: 'right',
        },
        {
            id: 'complete',
            title: 'Vous etes pret!',
            description: 'Vous connaissez maintenant les bases de Boxibox. N\'hesitez pas a explorer et a utiliser le bouton d\'aide si besoin.',
            tips: 'Notre equipe support est disponible si vous avez des questions.',
            target: null,
        },
    ],
    boxes: [
        {
            id: 'boxes-list',
            title: 'Liste des boxes',
            description: 'Visualisez tous vos boxes avec leur statut, taille et tarif. Utilisez les filtres pour affiner la recherche.',
            target: '[data-tutorial="boxes-table"]',
            position: 'top',
        },
        {
            id: 'box-status',
            title: 'Statuts des boxes',
            description: 'Vert = Disponible, Bleu = Loue, Orange = Reserve, Rouge = Maintenance.',
            target: '[data-tutorial="box-status-legend"]',
            position: 'bottom',
        },
        {
            id: 'add-box',
            title: 'Ajouter un box',
            description: 'Cliquez ici pour creer un nouveau box avec son numero, taille, etage et tarif.',
            target: '[data-tutorial="add-box-button"]',
            position: 'left',
        },
    ],
    contracts: [
        {
            id: 'contract-list',
            title: 'Vos contrats',
            description: 'Consultez tous vos contrats actifs, en attente ou termines.',
            target: '[data-tutorial="contracts-table"]',
            position: 'top',
        },
        {
            id: 'create-contract',
            title: 'Nouveau contrat',
            description: 'Creez un contrat en selectionnant un client et un box disponible.',
            target: '[data-tutorial="create-contract-button"]',
            position: 'left',
        },
    ],
}

// Computed
const steps = computed(() => tourConfigs[props.tourId] || tourConfigs.main)
const currentStep = computed(() => steps.value[currentStepIndex.value])
const isLastStep = computed(() => currentStepIndex.value === steps.value.length - 1)
const progressPercentage = computed(() => ((currentStepIndex.value + 1) / steps.value.length) * 100)

const arrowClasses = computed(() => {
    const classes = {
        top: '-top-2 left-1/2 -translate-x-1/2 border-l-0 border-t-0',
        bottom: '-bottom-2 left-1/2 -translate-x-1/2 border-r-0 border-b-0',
        left: '-left-2 top-1/2 -translate-y-1/2 border-t-0 border-l-0',
        right: '-right-2 top-1/2 -translate-y-1/2 border-b-0 border-r-0',
    }
    return classes[arrowDirection.value] || ''
})

// Methods
const startTour = () => {
    currentStepIndex.value = 0
    isActive.value = true
    emit('start')
    nextTick(positionTooltip)
}

const close = () => {
    isActive.value = false
    if (dontShowAgain.value) {
        saveTourPreference()
    }
}

const skipTour = () => {
    emit('skip')
    close()
}

const nextStep = () => {
    if (isLastStep.value) {
        emit('complete')
        close()
        // Navigate to next step if specified
        if (currentStep.value.nextPage) {
            router.visit(currentStep.value.nextPage)
        }
    } else {
        currentStepIndex.value++
        emit('step-change', currentStepIndex.value)
        nextTick(positionTooltip)
    }
}

const previousStep = () => {
    if (currentStepIndex.value > 0) {
        currentStepIndex.value--
        emit('step-change', currentStepIndex.value)
        nextTick(positionTooltip)
    }
}

const handleOverlayClick = (e) => {
    // Only close if clicking on the backdrop, not the spotlight
    if (e.target === e.currentTarget) {
        // Optional: could navigate to target element instead of closing
    }
}

const positionTooltip = () => {
    const step = currentStep.value
    if (!step) return

    // Reset
    spotlightStyle.value = null
    arrowDirection.value = null

    // If no target, center the tooltip
    if (!step.target) {
        tooltipStyle.value = {
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
        }
        return
    }

    // Find target element
    const targetEl = document.querySelector(step.target)
    if (!targetEl) {
        // Target not found, center tooltip
        tooltipStyle.value = {
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
        }
        return
    }

    // Get target position
    const rect = targetEl.getBoundingClientRect()
    const padding = 8

    // Create spotlight
    spotlightStyle.value = {
        top: `${rect.top - padding}px`,
        left: `${rect.left - padding}px`,
        width: `${rect.width + padding * 2}px`,
        height: `${rect.height + padding * 2}px`,
    }

    // Scroll target into view if needed
    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' })

    // Position tooltip based on preference
    const tooltipWidth = 360
    const tooltipHeight = 300 // Approximate
    const margin = 16
    const windowWidth = window.innerWidth
    const windowHeight = window.innerHeight

    let top, left
    const position = step.position || 'bottom'

    switch (position) {
        case 'top':
            top = rect.top - tooltipHeight - margin
            left = rect.left + rect.width / 2 - tooltipWidth / 2
            arrowDirection.value = 'bottom'
            break
        case 'bottom':
            top = rect.bottom + margin
            left = rect.left + rect.width / 2 - tooltipWidth / 2
            arrowDirection.value = 'top'
            break
        case 'left':
            top = rect.top + rect.height / 2 - tooltipHeight / 2
            left = rect.left - tooltipWidth - margin
            arrowDirection.value = 'right'
            break
        case 'right':
            top = rect.top + rect.height / 2 - tooltipHeight / 2
            left = rect.right + margin
            arrowDirection.value = 'left'
            break
    }

    // Ensure tooltip stays in viewport
    if (left < margin) left = margin
    if (left + tooltipWidth > windowWidth - margin) left = windowWidth - tooltipWidth - margin
    if (top < margin) top = margin
    if (top + tooltipHeight > windowHeight - margin) top = windowHeight - tooltipHeight - margin

    tooltipStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
    }
}

const saveTourPreference = () => {
    // Save to localStorage
    localStorage.setItem(`tutorial_${props.tourId}_completed`, 'true')

    // Also save to backend if user is logged in
    const page = usePage()
    if (page.props.auth?.user) {
        router.post(route('tenant.user.preferences'), {
            key: `tutorial_${props.tourId}_completed`,
            value: true
        }, { preserveScroll: true, preserveState: true })
    }
}

const checkAutoStart = () => {
    if (!props.autoStart) return

    const completed = localStorage.getItem(`tutorial_${props.tourId}_completed`)
    if (!completed) {
        // Delay start for better UX
        setTimeout(startTour, 1500)
    }
}

// Handle window resize
const handleResize = () => {
    if (isActive.value) {
        positionTooltip()
    }
}

// Keyboard navigation
const handleKeydown = (e) => {
    if (!isActive.value) return

    switch (e.key) {
        case 'Escape':
            close()
            break
        case 'ArrowRight':
        case 'Enter':
            nextStep()
            break
        case 'ArrowLeft':
            previousStep()
            break
    }
}

// Expose methods for external control
defineExpose({
    startTour,
    close,
    nextStep,
    previousStep,
    goToStep: (index) => {
        if (index >= 0 && index < steps.value.length) {
            currentStepIndex.value = index
            if (!isActive.value) isActive.value = true
            nextTick(positionTooltip)
        }
    }
})

// Lifecycle
onMounted(() => {
    window.addEventListener('resize', handleResize)
    window.addEventListener('keydown', handleKeydown)
    checkAutoStart()
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('keydown', handleKeydown)
})

// Watch for route changes
watch(() => usePage().url, () => {
    if (isActive.value) {
        // Optionally pause or continue tour on navigation
        nextTick(positionTooltip)
    }
})
</script>

<style scoped>
/* Fade transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Slide fade transition */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}
.slide-fade-enter-from {
    transform: translateY(10px);
    opacity: 0;
}
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

/* Bounce transition for floating button */
.bounce-enter-active {
    animation: bounce-in 0.5s;
}
.bounce-leave-active {
    animation: bounce-in 0.3s reverse;
}
@keyframes bounce-in {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
