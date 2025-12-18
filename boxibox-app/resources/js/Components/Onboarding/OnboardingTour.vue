<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
    XMarkIcon,
    ArrowRightIcon,
    ArrowLeftIcon,
    CheckIcon,
    SparklesIcon,
    RocketLaunchIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    steps: {
        type: Array,
        default: () => []
    },
    autoStart: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['complete', 'skip', 'step-change'])

const page = usePage()
const isActive = ref(false)
const currentStepIndex = ref(0)
const targetElement = ref(null)
const tooltipPosition = ref({ top: 0, left: 0 })
const highlightPosition = ref({ top: 0, left: 0, width: 0, height: 0 })
const showWelcome = ref(true)

const currentStep = computed(() => props.steps[currentStepIndex.value] || null)
const isFirstStep = computed(() => currentStepIndex.value === 0)
const isLastStep = computed(() => currentStepIndex.value === props.steps.length - 1)
const progress = computed(() => ((currentStepIndex.value + 1) / props.steps.length) * 100)

// Démarre le tour
const startTour = () => {
    showWelcome.value = false
    isActive.value = true
    currentStepIndex.value = 0
    nextTick(() => {
        highlightCurrentStep()
    })
}

// Termine le tour
const completeTour = () => {
    isActive.value = false
    showWelcome.value = false
    saveOnboardingProgress('completed')
    emit('complete')
}

// Passe le tour - marque comme montré pour ne plus afficher automatiquement
const skipTour = () => {
    isActive.value = false
    showWelcome.value = false

    // Marquer comme montré même en cas de skip
    localStorage.setItem('tutorial_welcome_shown', 'true')

    saveOnboardingProgress('skipped')
    emit('skip')
}

// Étape suivante
const nextStep = () => {
    if (currentStep.value?.action) {
        // Si l'étape a une action (navigation), l'exécuter
        executeAction(currentStep.value.action)
    } else if (isLastStep.value) {
        completeTour()
    } else {
        currentStepIndex.value++
        emit('step-change', currentStepIndex.value)
        nextTick(() => {
            highlightCurrentStep()
        })
    }
}

// Étape précédente
const prevStep = () => {
    if (!isFirstStep.value) {
        currentStepIndex.value--
        emit('step-change', currentStepIndex.value)
        nextTick(() => {
            highlightCurrentStep()
        })
    }
}

// Aller à une étape spécifique
const goToStep = (index) => {
    if (index >= 0 && index < props.steps.length) {
        currentStepIndex.value = index
        emit('step-change', currentStepIndex.value)
        nextTick(() => {
            highlightCurrentStep()
        })
    }
}

// Exécute une action (navigation)
const executeAction = (action) => {
    if (action.type === 'navigate') {
        router.visit(action.route, {
            onSuccess: () => {
                if (!isLastStep.value) {
                    currentStepIndex.value++
                    nextTick(() => {
                        setTimeout(() => highlightCurrentStep(), 500)
                    })
                } else {
                    completeTour()
                }
            }
        })
    } else if (action.type === 'click') {
        const element = document.querySelector(action.selector)
        if (element) {
            element.click()
        }
        if (!isLastStep.value) {
            currentStepIndex.value++
            nextTick(() => {
                highlightCurrentStep()
            })
        }
    }
}

// Met en surbrillance l'élément de l'étape actuelle
const highlightCurrentStep = () => {
    if (!currentStep.value?.target) {
        // Pas de cible, afficher au centre
        tooltipPosition.value = {
            top: window.innerHeight / 2 - 100,
            left: window.innerWidth / 2 - 200
        }
        highlightPosition.value = { top: 0, left: 0, width: 0, height: 0 }
        return
    }

    const element = document.querySelector(currentStep.value.target)
    if (!element) {
        // Élément non trouvé, réessayer après un délai
        setTimeout(() => highlightCurrentStep(), 300)
        return
    }

    targetElement.value = element
    const rect = element.getBoundingClientRect()
    const padding = 8

    // Position du highlight
    highlightPosition.value = {
        top: rect.top - padding + window.scrollY,
        left: rect.left - padding,
        width: rect.width + padding * 2,
        height: rect.height + padding * 2
    }

    // Scroll vers l'élément si nécessaire
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })

    // Calculer la position du tooltip
    calculateTooltipPosition(rect)
}

// Calcule la position optimale du tooltip
const calculateTooltipPosition = (targetRect) => {
    const tooltipWidth = 380
    const tooltipHeight = 200
    const margin = 20
    const position = currentStep.value?.position || 'auto'

    let top, left

    if (position === 'auto' || position === 'bottom') {
        // Essayer en bas
        top = targetRect.bottom + margin + window.scrollY
        left = targetRect.left + (targetRect.width / 2) - (tooltipWidth / 2)

        // Si ça dépasse en bas, mettre en haut
        if (top + tooltipHeight > window.innerHeight + window.scrollY) {
            top = targetRect.top - tooltipHeight - margin + window.scrollY
        }
    } else if (position === 'top') {
        top = targetRect.top - tooltipHeight - margin + window.scrollY
        left = targetRect.left + (targetRect.width / 2) - (tooltipWidth / 2)
    } else if (position === 'left') {
        top = targetRect.top + (targetRect.height / 2) - (tooltipHeight / 2) + window.scrollY
        left = targetRect.left - tooltipWidth - margin
    } else if (position === 'right') {
        top = targetRect.top + (targetRect.height / 2) - (tooltipHeight / 2) + window.scrollY
        left = targetRect.right + margin
    }

    // S'assurer que le tooltip reste dans la fenêtre
    left = Math.max(margin, Math.min(left, window.innerWidth - tooltipWidth - margin))
    top = Math.max(margin, top)

    tooltipPosition.value = { top, left }
}

// Sauvegarde la progression
const saveOnboardingProgress = (status) => {
    localStorage.setItem('onboarding_status', status)
    localStorage.setItem('onboarding_step', currentStepIndex.value.toString())

    // Marquer le tutoriel comme montré (pour éviter auto-démarrage futur)
    localStorage.setItem('tutorial_welcome_shown', 'true')

    // Sauvegarder côté serveur aussi
    if (route().has('tenant.onboarding.update')) {
        router.post(route('tenant.onboarding.update'), {
            status: status,
            step: currentStepIndex.value
        }, { preserveState: true, preserveScroll: true })
    }
}

/**
 * Vérifie si l'onboarding doit démarrer automatiquement
 * RÈGLE: Seulement la PREMIÈRE fois que l'utilisateur se connecte
 */
const checkAutoStart = () => {
    const status = localStorage.getItem('onboarding_status')
    const welcomeShown = localStorage.getItem('tutorial_welcome_shown')

    // Ne jamais auto-démarrer si le tutoriel a déjà été montré
    if (welcomeShown === 'true') {
        return
    }

    // Première visite: afficher le message de bienvenue
    if (!status && props.autoStart) {
        showWelcome.value = true
    } else if (status === 'in_progress') {
        // Reprendre un tour en cours
        const savedStep = parseInt(localStorage.getItem('onboarding_step') || '0')
        currentStepIndex.value = savedStep
        isActive.value = true
        showWelcome.value = false
        nextTick(() => highlightCurrentStep())
    }
}

// Gestion du resize
const handleResize = () => {
    if (isActive.value) {
        highlightCurrentStep()
    }
}

// Gestion des touches clavier
const handleKeydown = (e) => {
    if (!isActive.value) return

    if (e.key === 'Escape') {
        skipTour()
    } else if (e.key === 'ArrowRight' || e.key === 'Enter') {
        nextStep()
    } else if (e.key === 'ArrowLeft') {
        prevStep()
    }
}

onMounted(() => {
    checkAutoStart()
    window.addEventListener('resize', handleResize)
    window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('keydown', handleKeydown)
})

// Exposer les méthodes pour le composant parent
defineExpose({
    startTour,
    skipTour,
    goToStep,
    isActive
})
</script>

<template>
    <!-- Modal de bienvenue -->
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="showWelcome"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 backdrop-blur-sm"
            >
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
                    <!-- Header avec animation -->
                    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-8 py-10 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                            <RocketLaunchIcon class="w-10 h-10 text-white" />
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">
                            Bienvenue sur BoxiBox !
                        </h2>
                        <p class="text-primary-100">
                            Votre solution de gestion de self-stockage
                        </p>
                    </div>

                    <!-- Contenu -->
                    <div class="px-8 py-6">
                        <p class="text-gray-600 text-center mb-6">
                            Nous allons vous guider pas à pas pour configurer votre espace de travail
                            et découvrir les fonctionnalités principales.
                        </p>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-semibold">1</span>
                                </div>
                                <span>Créer votre premier site de stockage</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-semibold">2</span>
                                </div>
                                <span>Ajouter des boxes à louer</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-semibold">3</span>
                                </div>
                                <span>Gérer vos clients et contrats</span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button
                                @click="skipTour"
                                class="flex-1 px-4 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                            >
                                Passer le guide
                            </button>
                            <button
                                @click="startTour"
                                class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
                            >
                                <SparklesIcon class="w-5 h-5" />
                                Commencer
                            </button>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-8 py-4 bg-gray-50 text-center">
                        <p class="text-xs text-gray-500">
                            Durée estimée : 3-5 minutes
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Overlay et Highlight -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="isActive" class="fixed inset-0 z-[9998]">
                <!-- Overlay sombre avec trou -->
                <svg class="absolute inset-0 w-full h-full">
                    <defs>
                        <mask id="spotlight-mask">
                            <rect x="0" y="0" width="100%" height="100%" fill="white" />
                            <rect
                                :x="highlightPosition.left"
                                :y="highlightPosition.top"
                                :width="highlightPosition.width"
                                :height="highlightPosition.height"
                                rx="8"
                                fill="black"
                            />
                        </mask>
                    </defs>
                    <rect
                        x="0"
                        y="0"
                        width="100%"
                        height="100%"
                        fill="rgba(0,0,0,0.7)"
                        mask="url(#spotlight-mask)"
                    />
                </svg>

                <!-- Bordure lumineuse autour de l'élément -->
                <div
                    v-if="highlightPosition.width > 0"
                    class="absolute border-2 border-primary-400 rounded-lg pointer-events-none animate-pulse"
                    :style="{
                        top: highlightPosition.top + 'px',
                        left: highlightPosition.left + 'px',
                        width: highlightPosition.width + 'px',
                        height: highlightPosition.height + 'px',
                        boxShadow: '0 0 0 4px rgba(59, 130, 246, 0.3), 0 0 20px rgba(59, 130, 246, 0.4)'
                    }"
                />
            </div>
        </Transition>
    </Teleport>

    <!-- Tooltip -->
    <Teleport to="body">
        <Transition name="slide-fade">
            <div
                v-if="isActive && currentStep"
                class="fixed z-[9999] w-[380px] bg-white rounded-xl shadow-2xl"
                :style="{
                    top: tooltipPosition.top + 'px',
                    left: tooltipPosition.left + 'px'
                }"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b bg-gray-50 rounded-t-xl">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 bg-primary-600 text-white text-sm font-bold rounded-full">
                            {{ currentStepIndex + 1 }}
                        </span>
                        <span class="text-sm text-gray-500">
                            sur {{ steps.length }}
                        </span>
                    </div>
                    <button
                        @click="skipTour"
                        class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-200 rounded transition-colors"
                        title="Fermer le guide"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <!-- Barre de progression -->
                <div class="h-1 bg-gray-200">
                    <div
                        class="h-full bg-primary-600 transition-all duration-300"
                        :style="{ width: progress + '%' }"
                    />
                </div>

                <!-- Contenu -->
                <div class="px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        {{ currentStep.title }}
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        {{ currentStep.description }}
                    </p>

                    <!-- Astuce optionnelle -->
                    <div
                        v-if="currentStep.tip"
                        class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg"
                    >
                        <p class="text-xs text-amber-800">
                            <strong>Astuce :</strong> {{ currentStep.tip }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between px-5 py-4 bg-gray-50 rounded-b-xl">
                    <button
                        v-if="!isFirstStep"
                        @click="prevStep"
                        class="flex items-center gap-1 px-3 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-lg transition-colors text-sm"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Précédent
                    </button>
                    <div v-else />

                    <button
                        @click="nextStep"
                        class="flex items-center gap-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm font-medium"
                    >
                        <template v-if="isLastStep">
                            <CheckIcon class="w-4 h-4" />
                            Terminer
                        </template>
                        <template v-else>
                            {{ currentStep.action ? currentStep.actionLabel || 'Continuer' : 'Suivant' }}
                            <ArrowRightIcon class="w-4 h-4" />
                        </template>
                    </button>
                </div>

                <!-- Navigation par points -->
                <div class="flex justify-center gap-1.5 pb-4">
                    <button
                        v-for="(step, index) in steps"
                        :key="index"
                        @click="goToStep(index)"
                        :class="[
                            'w-2 h-2 rounded-full transition-all',
                            index === currentStepIndex
                                ? 'bg-primary-600 w-4'
                                : index < currentStepIndex
                                    ? 'bg-primary-300'
                                    : 'bg-gray-300'
                        ]"
                        :title="step.title"
                    />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

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
</style>
