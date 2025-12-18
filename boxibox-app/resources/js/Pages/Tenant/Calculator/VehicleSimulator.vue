<template>
    <TenantLayout title="Simulateur de Chargement Vehicule" :breadcrumbs="[{ label: 'Calculateur', href: route('tenant.calculator.index') }, { label: 'Simulateur Vehicule' }]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <TruckIcon class="w-8 h-8 text-blue-600" />
                            Simulateur de Chargement
                        </h2>
                        <p class="text-gray-500 mt-1">
                            Visualisez en 3D le remplissage de differents types de vehicules pour aider vos clients a estimer leurs besoins de stockage
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('tenant.calculator.index')" class="btn-secondary">
                            <ArrowLeftIcon class="w-4 h-4 mr-2" />
                            Retour
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-2xl border border-blue-100 p-6">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <LightBulbIcon class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-blue-900">Comment utiliser ce simulateur ?</h3>
                        <ul class="mt-2 text-sm text-blue-700 space-y-1">
                            <li class="flex items-center gap-2">
                                <CheckIcon class="w-4 h-4 text-blue-500" />
                                Selectionnez le type de vehicule que vous souhaitez charger
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckIcon class="w-4 h-4 text-blue-500" />
                                Cliquez sur les objets a droite pour les ajouter au chargement
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckIcon class="w-4 h-4 text-blue-500" />
                                Glissez la vue 3D pour faire pivoter le vehicule
                            </li>
                            <li class="flex items-center gap-2">
                                <CheckIcon class="w-4 h-4 text-blue-500" />
                                Modifiez les dimensions des objets en cliquant sur l'icone crayon
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Vehicle Loading Simulator -->
            <VehicleLoadingSimulator
                :editable="true"
                initial-vehicle="van"
                @update:fillPercentage="onFillUpdate"
                @itemsChanged="onItemsChanged"
            />

            <!-- Conversion Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Equivalence en Stockage</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ calculatedVolume.toFixed(1) }} m³</div>
                        <p class="text-sm text-gray-500 mt-1">Volume total</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-green-600">{{ recommendedSurface.toFixed(1) }} m²</div>
                        <p class="text-sm text-gray-500 mt-1">Surface recommandee</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-purple-600">{{ recommendedBoxSize }}</div>
                        <p class="text-sm text-gray-500 mt-1">Taille de box suggeree</p>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-blue-50 rounded-xl">
                    <p class="text-sm text-blue-700">
                        <span class="font-medium">Conseil:</span>
                        Pour un stockage optimal, prevoyez une marge de 20% supplementaire pour faciliter l'acces a vos affaires.
                        Un box de <strong>{{ recommendedSurfaceWithMargin.toFixed(1) }} m²</strong> serait ideal.
                    </p>
                </div>

                <!-- CTA -->
                <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                    <Link :href="route('tenant.boxes.index', { min_size: Math.ceil(recommendedSurface) })" class="btn-primary">
                        <CubeIcon class="w-5 h-5 mr-2" />
                        Voir les boxes disponibles
                    </Link>
                    <button @click="shareResults" class="btn-secondary">
                        <ShareIcon class="w-5 h-5 mr-2" />
                        Partager les resultats
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import VehicleLoadingSimulator from '@/Components/VehicleLoadingSimulator.vue'
import {
    TruckIcon,
    ArrowLeftIcon,
    LightBulbIcon,
    CheckIcon,
    CubeIcon,
    ShareIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: {
        type: Array,
        default: () => []
    }
})

// State
const fillPercentage = ref(0)
const loadedItems = ref([])

// Computed values
const calculatedVolume = computed(() => {
    return loadedItems.value.reduce((sum, item) => sum + (item.volume * item.quantity), 0)
})

const recommendedSurface = computed(() => {
    // Formula: volume / 2 (average stacking height of 2m)
    const surface = calculatedVolume.value / 2
    return Math.max(1, surface)
})

const recommendedSurfaceWithMargin = computed(() => {
    return recommendedSurface.value * 1.2
})

const recommendedBoxSize = computed(() => {
    const surface = recommendedSurface.value
    if (surface <= 2) return '2 m²'
    if (surface <= 4) return '4 m²'
    if (surface <= 6) return '6 m²'
    if (surface <= 8) return '8 m²'
    if (surface <= 10) return '10 m²'
    if (surface <= 15) return '15 m²'
    if (surface <= 20) return '20 m²'
    return '20+ m²'
})

// Event handlers
const onFillUpdate = (percentage) => {
    fillPercentage.value = percentage
}

const onItemsChanged = (items) => {
    loadedItems.value = items
}

const shareResults = () => {
    const text = `J'ai estime mes besoins de stockage avec le simulateur BoxiBox:
- Volume: ${calculatedVolume.value.toFixed(1)} m³
- Surface recommandee: ${recommendedSurface.value.toFixed(1)} m²
- Box suggere: ${recommendedBoxSize.value}`

    if (navigator.share) {
        navigator.share({
            title: 'Mon estimation de stockage BoxiBox',
            text: text,
            url: window.location.href
        })
    } else {
        navigator.clipboard.writeText(text)
        alert('Resultats copies dans le presse-papier!')
    }
}
</script>
