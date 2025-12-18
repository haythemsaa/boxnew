<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    BuildingOfficeIcon,
    CubeIcon,
    CurrencyEuroIcon,
    UserIcon,
    DocumentTextIcon,
    ReceiptRefundIcon,
    CogIcon,
    SparklesIcon,
    CheckCircleIcon,
    ArrowRightIcon,
    PlayIcon,
    LightBulbIcon,
    PencilIcon,
    ClockIcon,
    BellIcon,
    MapIcon,
    DevicePhoneMobileIcon,
    RocketLaunchIcon,
    AcademicCapIcon,
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    progress: Object,
    checklist: Array,
    tips: Array,
})

const getIcon = (iconName) => {
    const icons = {
        'building': BuildingOfficeIcon,
        'cube': CubeIcon,
        'currency': CurrencyEuroIcon,
        'user': UserIcon,
        'document': DocumentTextIcon,
        'receipt': ReceiptRefundIcon,
        'cog': CogIcon,
        'sparkles': SparklesIcon,
        'pencil': PencilIcon,
        'clock': ClockIcon,
        'bell': BellIcon,
        'map': MapIcon,
        'phone': DevicePhoneMobileIcon,
    }
    return icons[iconName] || SparklesIcon
}

const completedSteps = computed(() => props.checklist.filter(item => item.completed).length)
const totalSteps = computed(() => props.checklist.filter(item => !item.optional).length)

const startInteractiveTour = () => {
    // Réinitialiser le localStorage et déclencher le tour
    localStorage.removeItem('onboarding_status')
    localStorage.removeItem('onboarding_step')
    router.visit(route('tenant.dashboard'), {
        onSuccess: () => {
            // Le tour sera déclenché automatiquement
        }
    })
}

const resetOnboarding = () => {
    if (confirm('Voulez-vous vraiment réinitialiser le guide de démarrage ?')) {
        router.post(route('tenant.onboarding.reset'))
    }
}
</script>

<template>
    <Head title="Guide de démarrage" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 bg-primary-100 rounded-xl">
                            <AcademicCapIcon class="w-8 h-8 text-primary-600" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Guide de démarrage</h1>
                            <p class="text-gray-500">Configurez votre espace BoxiBox étape par étape</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Card -->
                <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-6 mb-8 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold">Votre progression</h2>
                            <p class="text-primary-100">{{ completedSteps }} sur {{ totalSteps }} étapes complétées</p>
                        </div>
                        <div class="text-right">
                            <div class="text-4xl font-bold">{{ progress.percentage }}%</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="h-3 bg-primary-800/30 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-white rounded-full transition-all duration-500"
                            :style="{ width: progress.percentage + '%' }"
                        />
                    </div>

                    <!-- Quick Action -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button
                            @click="startInteractiveTour"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white text-primary-700 rounded-lg font-medium hover:bg-primary-50 transition-colors"
                        >
                            <PlayIcon class="w-5 h-5" />
                            Lancer le tutoriel interactif
                        </button>
                        <Link
                            :href="route('tenant.dashboard')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500/30 text-white rounded-lg font-medium hover:bg-primary-500/50 transition-colors"
                        >
                            Aller au tableau de bord
                            <ArrowRightIcon class="w-4 h-4" />
                        </Link>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Checklist -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm border">
                            <div class="px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold text-gray-900">Liste des tâches</h3>
                            </div>

                            <div class="divide-y">
                                <div
                                    v-for="(item, index) in checklist"
                                    :key="item.id"
                                    :class="[
                                        'px-6 py-4 flex items-start gap-4 transition-colors',
                                        item.completed ? 'bg-green-50/50' : 'hover:bg-gray-50'
                                    ]"
                                >
                                    <!-- Icon/Status -->
                                    <div class="flex-shrink-0 mt-0.5">
                                        <div
                                            v-if="item.completed"
                                            class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center"
                                        >
                                            <CheckCircleSolidIcon class="w-6 h-6 text-green-600" />
                                        </div>
                                        <div
                                            v-else
                                            class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center"
                                        >
                                            <component :is="getIcon(item.icon)" class="w-5 h-5 text-gray-500" />
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 :class="[
                                                'font-medium',
                                                item.completed ? 'text-green-700' : 'text-gray-900'
                                            ]">
                                                {{ item.title }}
                                            </h4>
                                            <span
                                                v-if="item.count > 0"
                                                class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full"
                                            >
                                                {{ item.count }}
                                            </span>
                                            <span
                                                v-if="item.optional"
                                                class="px-2 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full"
                                            >
                                                Optionnel
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-0.5">{{ item.description }}</p>
                                    </div>

                                    <!-- Action -->
                                    <div class="flex-shrink-0">
                                        <Link
                                            v-if="!item.completed"
                                            :href="route(item.route)"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors"
                                        >
                                            Commencer
                                            <ArrowRightIcon class="w-4 h-4" />
                                        </Link>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-green-600"
                                        >
                                            <CheckCircleIcon class="w-4 h-4" />
                                            Terminé
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reset Option -->
                        <div class="mt-4 text-center">
                            <button
                                @click="resetOnboarding"
                                class="text-sm text-gray-500 hover:text-gray-700 underline"
                            >
                                Réinitialiser le guide de démarrage
                            </button>
                        </div>
                    </div>

                    <!-- Sidebar with Tips -->
                    <div class="space-y-6">
                        <!-- Tips Card -->
                        <div class="bg-white rounded-xl shadow-sm border">
                            <div class="px-5 py-4 border-b flex items-center gap-2">
                                <LightBulbIcon class="w-5 h-5 text-amber-500" />
                                <h3 class="font-semibold text-gray-900">Conseils utiles</h3>
                            </div>

                            <div class="p-5 space-y-4">
                                <div
                                    v-for="tip in tips"
                                    :key="tip.title"
                                    class="flex gap-3"
                                >
                                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                        <component :is="getIcon(tip.icon)" class="w-4 h-4 text-amber-600" />
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">{{ tip.title }}</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ tip.content }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Help Card -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-100">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <RocketLaunchIcon class="w-5 h-5 text-blue-600" />
                                </div>
                                <h3 class="font-semibold text-gray-900">Besoin d'aide ?</h3>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Notre équipe est là pour vous accompagner dans votre démarrage.
                            </p>
                            <div class="space-y-2">
                                <a
                                    href="#"
                                    class="block w-full px-4 py-2 text-center text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                                >
                                    Contacter le support
                                </a>
                                <a
                                    href="#"
                                    class="block w-full px-4 py-2 text-center text-sm font-medium text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                                >
                                    Voir la documentation
                                </a>
                            </div>
                        </div>

                        <!-- Video Tutorials -->
                        <div class="bg-white rounded-xl shadow-sm border p-5">
                            <h3 class="font-semibold text-gray-900 mb-3">Tutoriels vidéo</h3>
                            <div class="space-y-3">
                                <a
                                    href="#"
                                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <div class="w-16 h-10 bg-gray-200 rounded flex items-center justify-center">
                                        <PlayIcon class="w-5 h-5 text-gray-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Premiers pas</p>
                                        <p class="text-xs text-gray-500">3 min</p>
                                    </div>
                                </a>
                                <a
                                    href="#"
                                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <div class="w-16 h-10 bg-gray-200 rounded flex items-center justify-center">
                                        <PlayIcon class="w-5 h-5 text-gray-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Créer un contrat</p>
                                        <p class="text-xs text-gray-500">5 min</p>
                                    </div>
                                </a>
                                <a
                                    href="#"
                                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <div class="w-16 h-10 bg-gray-200 rounded flex items-center justify-center">
                                        <PlayIcon class="w-5 h-5 text-gray-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Facturation</p>
                                        <p class="text-xs text-gray-500">4 min</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
