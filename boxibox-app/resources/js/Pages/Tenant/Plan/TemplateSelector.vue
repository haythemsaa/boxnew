<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    Créer un nouveau plan
                </h1>
                <p class="text-xl text-gray-600">
                    Choisissez un modèle de départ ou créez un plan vierge
                </p>
            </div>

            <!-- Templates Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <!-- Blank Plan -->
                <div
                    @click="selectTemplate(null)"
                    class="group cursor-pointer"
                >
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border-2 border-transparent hover:border-primary-500">
                        <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <div class="text-center">
                                <PlusIcon class="h-16 w-16 text-gray-400 mx-auto mb-2" />
                                <p class="text-gray-500 font-medium">Plan vierge</p>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                Plan personnalisé
                            </h3>
                            <p class="text-gray-600 text-sm mb-4">
                                Commencez avec une toile vierge et créez votre propre mise en page
                            </p>
                            <button
                                class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors"
                            >
                                Sélectionner
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Templates from Database -->
                <div
                    v-for="template in templates"
                    :key="template.id"
                    @click="selectTemplate(template)"
                    class="group cursor-pointer"
                >
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border-2 border-transparent hover:border-primary-500">
                        <div class="h-48 bg-gray-100 flex items-center justify-center relative">
                            <!-- Template Preview -->
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-50 to-blue-100">
                                <svg :width="template.width / 5" :height="template.height / 5" class="text-blue-300">
                                    <rect width="100%" height="100%" fill="white" stroke="#e5e7eb" stroke-width="2" />
                                </svg>
                            </div>
                            <div v-if="template.category" class="absolute top-3 right-3">
                                <span class="px-3 py-1 bg-primary-500 text-white text-xs font-semibold rounded-full">
                                    {{ getCategoryLabel(template.category) }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                {{ template.name }}
                            </h3>
                            <p v-if="template.description" class="text-gray-600 text-sm mb-4">
                                {{ template.description }}
                            </p>
                            <div class="flex gap-2">
                                <button
                                    class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium"
                                >
                                    Utiliser
                                </button>
                                <button
                                    @click.stop="viewTemplate(template)"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                                >
                                    <EyeIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Templates -->
            <div v-if="featuredTemplates.length" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    Modèles recommandés
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                        v-for="template in featuredTemplates"
                        :key="'featured-' + template.id"
                        @click="selectTemplate(template)"
                        class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-2xl shadow-xl p-8 text-white cursor-pointer hover:shadow-2xl transition-all duration-300 transform hover:scale-105"
                    >
                        <h3 class="text-2xl font-bold mb-2">{{ template.name }}</h3>
                        <p class="text-primary-100 mb-6">{{ template.description }}</p>
                        <button class="px-6 py-2 bg-white text-primary-600 rounded-lg font-semibold hover:bg-primary-50 transition-colors">
                            Commencer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Edits -->
            <div v-if="recentPlans.length" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    Plans récents
                </h2>
                <div class="bg-white rounded-2xl shadow-lg divide-y">
                    <div
                        v-for="plan in recentPlans"
                        :key="plan.id"
                        class="flex items-center justify-between p-6 hover:bg-gray-50 transition-colors cursor-pointer"
                        @click="openPlan(plan)"
                    >
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ plan.name }}</h4>
                            <p class="text-sm text-gray-500">
                                Modifié le {{ formatDate(plan.updated_at) }}
                            </p>
                        </div>
                        <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-8 text-center">
                <QuestionMarkCircleIcon class="h-8 w-8 text-blue-600 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Besoin d'aide ?
                </h3>
                <p class="text-gray-600 mb-4">
                    Consultez notre documentation pour apprendre à créer et gérer des plans
                </p>
                <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Voir la documentation
                </button>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <transition name="modal">
            <div v-if="selectedTemplate" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        {{ selectedTemplate.name || 'Plan vierge' }}
                    </h2>
                    <p v-if="selectedTemplate.description" class="text-gray-600 mb-6">
                        {{ selectedTemplate.description }}
                    </p>
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center">
                            <span class="text-gray-700 font-medium mr-4">Dimensions :</span>
                            <span class="text-gray-600">{{ selectedTemplate.width || 1000 }} x {{ selectedTemplate.height || 1000 }} px</span>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="selectedTemplate = null"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                        >
                            Annuler
                        </button>
                        <button
                            @click="createPlanWithTemplate"
                            class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium"
                        >
                            Créer
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
    PlusIcon,
    EyeIcon,
    ChevronRightIcon,
    QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    templates: Array,
    recentPlans: Array,
})

const selectedTemplate = ref(null)

const featuredTemplates = computed(() => {
    return props.templates?.filter(t => t.category === 'premium').slice(0, 2) || []
})

const selectTemplate = (template) => {
    selectedTemplate.value = template
}

const createPlanWithTemplate = () => {
    const form = useForm({
        template_id: selectedTemplate.value?.id || null,
        template_data: selectedTemplate.value?.template_data || null,
    })

    form.post(route('tenant.plan.create'), {
        onSuccess: () => {
            selectedTemplate.value = null
        }
    })
}

const viewTemplate = (template) => {
    // Preview template details
    console.log('View template:', template)
}

const openPlan = (plan) => {
    window.location.href = route('tenant.plan.editor', { site: plan.site_id })
}

const getCategoryLabel = (category) => {
    const labels = {
        standard: 'Standard',
        premium: 'Premium',
        custom: 'Personnalisé',
    }
    return labels[category] || category
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.95);
}
</style>
