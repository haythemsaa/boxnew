<template>
    <MobileLayout title="Reserver un Box">
        <!-- Step Indicator -->
        <div class="flex items-center justify-between mb-6 px-4">
            <div v-for="(stepInfo, index) in steps" :key="index" class="flex items-center">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm"
                    :class="step >= index + 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500'"
                >
                    {{ index + 1 }}
                </div>
                <div v-if="index < steps.length - 1" class="w-8 h-0.5 mx-1" :class="step > index + 1 ? 'bg-primary-600' : 'bg-gray-200'"></div>
            </div>
        </div>

        <!-- Step 1: Select Site -->
        <div v-if="step === 1">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Choisir un site</h2>

            <!-- Search -->
            <div class="relative mb-4">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                    v-model="siteSearch"
                    type="text"
                    placeholder="Rechercher par ville ou nom..."
                    class="w-full pl-10 pr-4 py-3 bg-white border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500"
                />
            </div>

            <!-- Sites List -->
            <div class="space-y-3">
                <button
                    v-for="site in filteredSites"
                    :key="site.id"
                    @click="selectSite(site)"
                    class="w-full bg-white rounded-xl shadow-sm p-4 text-left hover:shadow-md transition"
                    :class="selectedSite?.id === site.id ? 'ring-2 ring-primary-500' : ''"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mr-3">
                                <BuildingOfficeIcon class="w-6 h-6 text-primary-600" />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ site.name }}</h3>
                                <p class="text-sm text-gray-500 flex items-center mt-0.5">
                                    <MapPinIcon class="w-4 h-4 mr-1" />
                                    {{ site.city }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-green-600">{{ site.available_boxes }} dispo</p>
                            <p class="text-xs text-gray-400">Des {{ site.min_price }}€/mois</p>
                        </div>
                    </div>
                </button>
            </div>

            <div v-if="filteredSites.length === 0" class="text-center py-12">
                <MapPinIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <p class="text-gray-500">Aucun site trouve</p>
            </div>
        </div>

        <!-- Step 2: Select Box Size -->
        <div v-if="step === 2">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Choisir un box</h2>
            <p class="text-gray-500 mb-4">Site: {{ selectedSite?.name }}</p>

            <!-- Calculator Link -->
            <Link
                :href="route('calculator.index')"
                class="block bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-4 text-white mb-4"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold">Pas sur de la taille ?</h4>
                        <p class="text-sm text-blue-100">Utilisez notre calculateur</p>
                    </div>
                    <CalculatorIcon class="w-8 h-8 text-white/80" />
                </div>
            </Link>

            <!-- Filter by size -->
            <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
                <button
                    v-for="size in sizeFilters"
                    :key="size.value"
                    @click="selectedSize = size.value"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                        selectedSize === size.value ? 'bg-primary-600 text-white' : 'bg-white text-gray-700'
                    ]"
                >
                    {{ size.label }}
                </button>
            </div>

            <!-- Available Boxes -->
            <div class="space-y-3">
                <button
                    v-for="box in filteredBoxes"
                    :key="box.id"
                    @click="selectBox(box)"
                    class="w-full bg-white rounded-xl shadow-sm overflow-hidden text-left hover:shadow-md transition"
                    :class="selectedBox?.id === box.id ? 'ring-2 ring-primary-500' : ''"
                >
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-gray-900">{{ box.name }}</h3>
                                <p class="text-sm text-gray-500">{{ box.dimensions }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-primary-600">{{ box.price }}€</p>
                                <p class="text-xs text-gray-400">/mois</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center space-x-4">
                                <div class="text-center">
                                    <p class="text-xs text-gray-400">Surface</p>
                                    <p class="font-semibold text-gray-900">{{ box.area }} m²</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-400">Volume</p>
                                    <p class="font-semibold text-gray-900">{{ box.volume }} m³</p>
                                </div>
                            </div>
                            <span
                                v-if="box.promo"
                                class="px-2 py-1 bg-red-100 text-red-600 text-xs font-medium rounded-full"
                            >
                                -{{ box.promo }}%
                            </span>
                        </div>
                    </div>
                </button>
            </div>

            <div v-if="filteredBoxes.length === 0" class="text-center py-12">
                <CubeIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <p class="text-gray-500">Aucun box disponible</p>
            </div>
        </div>

        <!-- Step 3: Select Dates -->
        <div v-if="step === 3">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Dates de location</h2>
            <p class="text-gray-500 mb-4">Box: {{ selectedBox?.name }}</p>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de debut</label>
                    <input
                        type="date"
                        v-model="startDate"
                        :min="minStartDate"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Duree du contrat</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="duration in durations"
                            :key="duration.months"
                            @click="selectedDuration = duration.months"
                            :class="[
                                'py-3 rounded-xl text-center font-medium transition',
                                selectedDuration === duration.months
                                    ? 'bg-primary-600 text-white'
                                    : 'bg-gray-100 text-gray-700'
                            ]"
                        >
                            {{ duration.label }}
                        </button>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-500">Date de fin</span>
                        <span class="font-medium text-gray-900">{{ computedEndDate }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">Prix total</span>
                        <span class="font-bold text-primary-600">{{ computedTotalPrice }}€</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Confirmation -->
        <div v-if="step === 4">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Confirmation</h2>

            <!-- Summary -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <h3 class="font-semibold text-gray-900 mb-3">Recapitulatif</h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Site</span>
                        <span class="font-medium text-gray-900">{{ selectedSite?.name }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Box</span>
                        <span class="font-medium text-gray-900">{{ selectedBox?.name }} ({{ selectedBox?.area }} m²)</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Debut</span>
                        <span class="font-medium text-gray-900">{{ formatDate(startDate) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Fin</span>
                        <span class="font-medium text-gray-900">{{ computedEndDate }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Duree</span>
                        <span class="font-medium text-gray-900">{{ selectedDuration }} mois</span>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <h3 class="font-semibold text-gray-900 mb-3">Tarification</h3>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Prix mensuel</span>
                        <span class="text-gray-900">{{ selectedBox?.price }}€</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">x {{ selectedDuration }} mois</span>
                        <span class="text-gray-900">{{ computedTotalPrice }}€</span>
                    </div>
                    <div v-if="selectedBox?.deposit" class="flex justify-between">
                        <span class="text-gray-500">Depot de garantie</span>
                        <span class="text-gray-900">{{ selectedBox.deposit }}€</span>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="font-semibold text-gray-900">Premier paiement</span>
                        <span class="font-bold text-primary-600 text-lg">{{ firstPayment }}€</span>
                    </div>
                </div>
            </div>

            <!-- Terms -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <label class="flex items-start">
                    <input
                        type="checkbox"
                        v-model="acceptTerms"
                        class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-3 text-sm text-gray-600">
                        J'accepte les <a href="#" class="text-primary-600">conditions generales</a> et la <a href="#" class="text-primary-600">politique de confidentialite</a>
                    </span>
                </label>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="fixed bottom-24 left-4 right-4 flex space-x-3">
            <button
                v-if="step > 1"
                @click="previousStep"
                class="flex-1 py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl"
            >
                Retour
            </button>
            <button
                @click="nextStep"
                :disabled="!canProceed"
                class="flex-1 py-3.5 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ step === 4 ? 'Confirmer la reservation' : 'Continuer' }}
            </button>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    MagnifyingGlassIcon,
    MapPinIcon,
    BuildingOfficeIcon,
    CubeIcon,
    CalculatorIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: Array,
    boxes: Array,
})

const step = ref(1)
const steps = ['Site', 'Box', 'Dates', 'Confirmation']

// Step 1
const siteSearch = ref('')
const selectedSite = ref(null)

// Step 2
const selectedSize = ref('all')
const selectedBox = ref(null)

// Step 3
const startDate = ref(new Date().toISOString().split('T')[0])
const selectedDuration = ref(1)

// Step 4
const acceptTerms = ref(false)

const sizeFilters = [
    { value: 'all', label: 'Tous' },
    { value: 'small', label: '1-3 m²' },
    { value: 'medium', label: '4-8 m²' },
    { value: 'large', label: '9-15 m²' },
    { value: 'xlarge', label: '15+ m²' },
]

const durations = [
    { months: 1, label: '1 mois' },
    { months: 3, label: '3 mois' },
    { months: 6, label: '6 mois' },
    { months: 12, label: '12 mois' },
]

const minStartDate = computed(() => {
    return new Date().toISOString().split('T')[0]
})

const filteredSites = computed(() => {
    if (!siteSearch.value) return props.sites || []
    const query = siteSearch.value.toLowerCase()
    return (props.sites || []).filter(s =>
        s.name.toLowerCase().includes(query) ||
        s.city.toLowerCase().includes(query)
    )
})

const filteredBoxes = computed(() => {
    let boxes = (props.boxes || []).filter(b => b.site_id === selectedSite.value?.id && b.status === 'available')

    if (selectedSize.value !== 'all') {
        boxes = boxes.filter(b => {
            const area = b.area
            switch (selectedSize.value) {
                case 'small': return area >= 1 && area <= 3
                case 'medium': return area >= 4 && area <= 8
                case 'large': return area >= 9 && area <= 15
                case 'xlarge': return area > 15
                default: return true
            }
        })
    }

    return boxes
})

const computedEndDate = computed(() => {
    if (!startDate.value) return '-'
    const date = new Date(startDate.value)
    date.setMonth(date.getMonth() + selectedDuration.value)
    return formatDate(date)
})

const computedTotalPrice = computed(() => {
    if (!selectedBox.value) return 0
    return selectedBox.value.price * selectedDuration.value
})

const firstPayment = computed(() => {
    if (!selectedBox.value) return 0
    return selectedBox.value.price + (selectedBox.value.deposit || 0)
})

const canProceed = computed(() => {
    switch (step.value) {
        case 1: return selectedSite.value !== null
        case 2: return selectedBox.value !== null
        case 3: return startDate.value && selectedDuration.value
        case 4: return acceptTerms.value
        default: return false
    }
})

const formatDate = (date) => {
    if (!date) return '-'
    const d = typeof date === 'string' ? new Date(date) : date
    return d.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
}

const selectSite = (site) => {
    selectedSite.value = site
}

const selectBox = (box) => {
    selectedBox.value = box
}

const previousStep = () => {
    if (step.value > 1) step.value--
}

const nextStep = () => {
    if (step.value < 4) {
        step.value++
    } else {
        submitReservation()
    }
}

const submitReservation = () => {
    router.post(route('mobile.reserve.store'), {
        site_id: selectedSite.value.id,
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        duration_months: selectedDuration.value,
        accept_terms: acceptTerms.value,
    })
}
</script>
