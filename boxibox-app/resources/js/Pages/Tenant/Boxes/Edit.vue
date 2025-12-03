<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    CubeIcon,
    BuildingOfficeIcon,
    CurrencyEuroIcon,
    Cog6ToothIcon,
    CheckIcon,
    BoltIcon,
    WifiIcon,
    ShieldCheckIcon,
    ClockIcon,
    HomeIcon,
    Square3Stack3DIcon,
    KeyIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    box: Object,
    sites: Array,
    buildings: Array,
    floors: Array,
})

const currentStep = ref(1)
const steps = [
    { number: 1, title: 'Informations', icon: CubeIcon },
    { number: 2, title: 'Dimensions', icon: Square3Stack3DIcon },
    { number: 3, title: 'Équipements', icon: Cog6ToothIcon },
]

const form = useForm({
    site_id: props.box.site_id,
    building_id: props.box.building_id,
    floor_id: props.box.floor_id,
    name: props.box.name,
    code: props.box.code,
    description: props.box.description,
    length: props.box.length,
    width: props.box.width,
    height: props.box.height,
    base_price: props.box.base_price,
    status: props.box.status,
    climate_controlled: props.box.climate_controlled,
    has_electricity: props.box.has_electricity,
    has_alarm: props.box.has_alarm,
    has_24_7_access: props.box.has_24_7_access,
    has_wifi: props.box.has_wifi,
    has_shelving: props.box.has_shelving,
    is_ground_floor: props.box.is_ground_floor,
    access_code: props.box.access_code,
    notes: props.box.notes,
})

const statuses = [
    { value: 'available', label: 'Disponible', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    { value: 'occupied', label: 'Occupé', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    { value: 'maintenance', label: 'Maintenance', color: 'bg-amber-100 text-amber-700 border-amber-200' },
    { value: 'reserved', label: 'Réservé', color: 'bg-purple-100 text-purple-700 border-purple-200' },
]

const features = [
    { key: 'climate_controlled', label: 'Climatisé', description: 'Température contrôlée', icon: Cog6ToothIcon },
    { key: 'has_electricity', label: 'Électricité', description: 'Prise électrique disponible', icon: BoltIcon },
    { key: 'has_alarm', label: 'Alarme', description: 'Système de sécurité', icon: ShieldCheckIcon },
    { key: 'has_24_7_access', label: 'Accès 24/7', description: 'Accessible à tout moment', icon: ClockIcon },
    { key: 'has_wifi', label: 'WiFi', description: 'Connexion internet', icon: WifiIcon },
    { key: 'has_shelving', label: 'Rayonnages', description: 'Étagères incluses', icon: Square3Stack3DIcon },
    { key: 'is_ground_floor', label: 'Rez-de-chaussée', description: 'Accès de plain-pied', icon: HomeIcon },
]

const calculatedVolume = computed(() => {
    if (form.length && form.width && form.height) {
        return (form.length * form.width * form.height).toFixed(2)
    }
    return null
})

const calculatedArea = computed(() => {
    if (form.length && form.width) {
        return (form.length * form.width).toFixed(2)
    }
    return null
})

const filteredBuildings = computed(() => {
    if (!form.site_id) return []
    return props.buildings?.filter(b => b.site_id == form.site_id) || []
})

const filteredFloors = computed(() => {
    if (!form.building_id) return []
    return props.floors?.filter(f => f.building_id == form.building_id) || []
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const nextStep = () => {
    if (currentStep.value < steps.length) currentStep.value++
}

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--
}

const submit = () => {
    form.put(route('tenant.boxes.update', props.box.id))
}
</script>

<template>
    <TenantLayout title="Modifier le box">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-amber-50 to-orange-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.boxes.index')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 mb-4 transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Retour aux boxes
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg">
                            <CubeIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Modifier le box</h1>
                            <p class="mt-1 text-gray-600">{{ box.code }}</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <template v-for="(step, index) in steps" :key="step.number">
                            <div class="flex items-center">
                                <div
                                    :class="[
                                        'flex items-center justify-center w-10 h-10 rounded-xl transition-all duration-300',
                                        currentStep >= step.number
                                            ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg'
                                            : 'bg-white text-gray-400 border-2 border-gray-200'
                                    ]"
                                >
                                    <component :is="step.icon" class="w-5 h-5" />
                                </div>
                                <span
                                    :class="[
                                        'ml-3 text-sm font-medium hidden sm:block',
                                        currentStep >= step.number ? 'text-gray-900' : 'text-gray-400'
                                    ]"
                                >
                                    {{ step.title }}
                                </span>
                            </div>
                            <div
                                v-if="index < steps.length - 1"
                                :class="[
                                    'flex-1 h-1 mx-4 rounded-full transition-all duration-300',
                                    currentStep > step.number ? 'bg-gradient-to-r from-amber-500 to-orange-600' : 'bg-gray-200'
                                ]"
                            ></div>
                        </template>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Step 1: Informations de base -->
                    <div v-show="currentStep === 1" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <CubeIcon class="w-5 h-5 text-amber-600" />
                                Informations de base
                            </h3>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Nom du box <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 transition-colors"
                                        placeholder="ex: Box A-101"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Code du box <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.code"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 uppercase transition-colors"
                                        placeholder="ex: BOX-A101"
                                    />
                                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Site <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.site_id"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 transition-colors"
                                    >
                                        <option value="">Sélectionner un site</option>
                                        <option v-for="site in sites" :key="site.id" :value="site.id">
                                            {{ site.name }} ({{ site.code }})
                                        </option>
                                    </select>
                                    <p v-if="form.errors.site_id" class="mt-1 text-sm text-red-600">{{ form.errors.site_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Bâtiment (Optionnel)
                                    </label>
                                    <select
                                        v-model="form.building_id"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 transition-colors"
                                    >
                                        <option value="">Aucun bâtiment</option>
                                        <option v-for="building in filteredBuildings" :key="building.id" :value="building.id">
                                            {{ building.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Étage (Optionnel)
                                    </label>
                                    <select
                                        v-model="form.floor_id"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 transition-colors"
                                    >
                                        <option value="">Aucun étage</option>
                                        <option v-for="floor in filteredFloors" :key="floor.id" :value="floor.id">
                                            {{ floor.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 transition-colors"
                                        placeholder="Description du box (optionnel)"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Statut -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Statut <span class="text-red-500">*</span>
                                </label>
                                <div class="flex flex-wrap gap-3">
                                    <label
                                        v-for="status in statuses"
                                        :key="status.value"
                                        class="relative cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.status"
                                            :value="status.value"
                                            class="peer sr-only"
                                        />
                                        <div :class="[
                                            'px-4 py-2 rounded-full border-2 font-medium text-sm transition-all',
                                            form.status === status.value
                                                ? status.color + ' border-current'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                        ]">
                                            {{ status.label }}
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Dimensions & Prix -->
                    <div v-show="currentStep === 2" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <Square3Stack3DIcon class="w-5 h-5 text-amber-600" />
                                Dimensions & Tarification
                            </h3>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Longueur (m) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form.length"
                                            type="number"
                                            step="0.1"
                                            min="0.1"
                                            required
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 pr-10 transition-colors"
                                            placeholder="2.5"
                                        />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">m</span>
                                    </div>
                                    <p v-if="form.errors.length" class="mt-1 text-sm text-red-600">{{ form.errors.length }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Largeur (m) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form.width"
                                            type="number"
                                            step="0.1"
                                            min="0.1"
                                            required
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 pr-10 transition-colors"
                                            placeholder="2.0"
                                        />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">m</span>
                                    </div>
                                    <p v-if="form.errors.width" class="mt-1 text-sm text-red-600">{{ form.errors.width }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Hauteur (m) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form.height"
                                            type="number"
                                            step="0.1"
                                            min="0.1"
                                            required
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 pr-10 transition-colors"
                                            placeholder="2.5"
                                        />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">m</span>
                                    </div>
                                    <p v-if="form.errors.height" class="mt-1 text-sm text-red-600">{{ form.errors.height }}</p>
                                </div>
                            </div>

                            <!-- Volume calculé -->
                            <div v-if="calculatedVolume || calculatedArea" class="mt-6 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-100">
                                <div class="flex items-center gap-8">
                                    <div v-if="calculatedArea" class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                            <Square3Stack3DIcon class="w-5 h-5 text-amber-600" />
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Surface</p>
                                            <p class="font-bold text-gray-900">{{ calculatedArea }} m²</p>
                                        </div>
                                    </div>
                                    <div v-if="calculatedVolume" class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                            <CubeIcon class="w-5 h-5 text-orange-600" />
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Volume</p>
                                            <p class="font-bold text-gray-900">{{ calculatedVolume }} m³</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Prix de base (€/mois) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium">€</span>
                                        </div>
                                        <input
                                            v-model.number="form.base_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="block w-full pl-10 pr-16 rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 text-xl font-bold transition-colors"
                                            placeholder="150.00"
                                        />
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">/mois</span>
                                    </div>
                                    <p v-if="form.errors.base_price" class="mt-1 text-sm text-red-600">{{ form.errors.base_price }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Code d'accès
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <KeyIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.access_code"
                                            type="text"
                                            class="block w-full pl-10 rounded-xl border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 transition-colors"
                                            placeholder="ex: 1234"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Équipements -->
                    <div v-show="currentStep === 3" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <Cog6ToothIcon class="w-5 h-5 text-amber-600" />
                                Équipements & Services
                            </h3>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <label
                                    v-for="feature in features"
                                    :key="feature.key"
                                    class="relative cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="form[feature.key]"
                                        class="peer sr-only"
                                    />
                                    <div :class="[
                                        'p-4 rounded-xl border-2 transition-all flex items-center gap-3',
                                        'peer-checked:border-teal-500 peer-checked:bg-teal-50',
                                        'hover:border-teal-300 hover:bg-teal-50/50',
                                        !form[feature.key] ? 'border-gray-200' : ''
                                    ]">
                                        <div :class="[
                                            'w-10 h-10 rounded-lg flex items-center justify-center transition-colors',
                                            form[feature.key] ? 'bg-teal-500 text-white' : 'bg-gray-100 text-gray-400'
                                        ]">
                                            <component :is="feature.icon" class="w-5 h-5" />
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-900 block">{{ feature.label }}</span>
                                            <span class="text-xs text-gray-500">{{ feature.description }}</span>
                                        </div>
                                        <CheckIcon v-if="form[feature.key]" class="w-5 h-5 text-teal-500" />
                                    </div>
                                </label>
                            </div>

                            <!-- Notes -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes additionnelles
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 transition-colors"
                                    placeholder="Instructions spéciales ou remarques..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
                        <div>
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="px-6 py-2.5 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                            >
                                Précédent
                            </button>
                            <Link
                                v-else
                                :href="route('tenant.boxes.index')"
                                class="px-6 py-2.5 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors inline-block"
                            >
                                Annuler
                            </Link>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                v-if="currentStep < steps.length"
                                type="button"
                                @click="nextStep"
                                class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-medium hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg"
                            >
                                Suivant
                            </button>
                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mise à jour...
                                </span>
                                <span v-else>Mettre à jour le box</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
