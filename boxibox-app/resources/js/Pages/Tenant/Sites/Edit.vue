<template>
    <TenantLayout :title="`Modifier ${site.name}`">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-purple-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.sites.show', site.id)"
                        class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 transition-colors mb-4"
                    >
                        <ArrowLeftIcon class="w-5 h-5 mr-2" />
                        Retour au site
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/25">
                            <MapPinIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Modifier le site</h1>
                            <p class="text-gray-500 mt-1">{{ site.name }} - {{ site.code }}</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div v-for="step in steps" :key="step.number" class="flex items-center" :class="step.number < steps.length ? 'flex-1' : ''">
                            <div class="flex flex-col items-center">
                                <div :class="[
                                    'w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg transition-all duration-300',
                                    currentStep === step.number
                                        ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/25'
                                        : currentStep > step.number
                                            ? 'bg-emerald-500 text-white'
                                            : 'bg-gray-100 text-gray-400'
                                ]">
                                    <CheckIcon v-if="currentStep > step.number" class="w-6 h-6" />
                                    <component v-else :is="step.icon" class="w-6 h-6" />
                                </div>
                                <span :class="[
                                    'mt-2 text-sm font-medium transition-colors',
                                    currentStep >= step.number ? 'text-indigo-600' : 'text-gray-400'
                                ]">{{ step.title }}</span>
                            </div>
                            <div v-if="step.number < steps.length" :class="[
                                'flex-1 h-1 mx-4 rounded-full transition-colors',
                                currentStep > step.number ? 'bg-emerald-500' : 'bg-gray-200'
                            ]"></div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <form @submit.prevent="submit">
                        <!-- Step 1: Informations du Site -->
                        <div v-if="currentStep === 1" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                                    <BuildingOfficeIcon class="w-5 h-5 text-indigo-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Informations du Site</h3>
                                    <p class="text-sm text-gray-500">Identifiez votre site de stockage</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Nom du site -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Nom du site <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500/10': form.errors.name }"
                                        placeholder="ex: Paris Centre"
                                    />
                                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <!-- Code du site -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Code du site <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.code"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all uppercase"
                                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500/10': form.errors.code }"
                                        placeholder="ex: PAR-001"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Identifiant unique pour ce site (sera affiché en majuscules)</p>
                                    <p v-if="form.errors.code" class="mt-2 text-sm text-red-600">{{ form.errors.code }}</p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all resize-none"
                                        placeholder="Description du site (optionnel)"
                                    ></textarea>
                                </div>

                                <!-- Statut -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Statut <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-3 gap-4">
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
                                                'p-4 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-indigo-500 peer-checked:bg-indigo-50',
                                                'hover:border-indigo-300 hover:bg-indigo-50/50',
                                                form.status !== status.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="[
                                                    'w-10 h-10 rounded-full mx-auto mb-2 flex items-center justify-center',
                                                    status.bgColor
                                                ]">
                                                    <component :is="status.icon" class="w-5 h-5" :class="status.iconColor" />
                                                </div>
                                                <span class="font-medium text-gray-900 text-sm">{{ status.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Adresse -->
                        <div v-if="currentStep === 2" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <MapPinIcon class="w-5 h-5 text-purple-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Adresse du Site</h3>
                                    <p class="text-sm text-gray-500">Localisation géographique du site</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Adresse -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Adresse <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.address"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                                        :class="{ 'border-red-300': form.errors.address }"
                                        placeholder="ex: 123 Rue de Rivoli"
                                    />
                                    <p v-if="form.errors.address" class="mt-2 text-sm text-red-600">{{ form.errors.address }}</p>
                                </div>

                                <!-- Ville & Code Postal -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Ville <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.city"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                                            :class="{ 'border-red-300': form.errors.city }"
                                            placeholder="ex: Paris"
                                        />
                                        <p v-if="form.errors.city" class="mt-2 text-sm text-red-600">{{ form.errors.city }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Code Postal <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.postal_code"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                                            :class="{ 'border-red-300': form.errors.postal_code }"
                                            placeholder="ex: 75001"
                                        />
                                        <p v-if="form.errors.postal_code" class="mt-2 text-sm text-red-600">{{ form.errors.postal_code }}</p>
                                    </div>
                                </div>

                                <!-- Pays -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Pays <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.country"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                                    >
                                        <option value="">Sélectionner un pays</option>
                                        <option value="France">France</option>
                                        <option value="Belgique">Belgique</option>
                                        <option value="Suisse">Suisse</option>
                                        <option value="Luxembourg">Luxembourg</option>
                                        <option value="Canada">Canada</option>
                                        <option value="Maroc">Maroc</option>
                                        <option value="Tunisie">Tunisie</option>
                                        <option value="Algérie">Algérie</option>
                                    </select>
                                </div>

                                <!-- Coordonnées GPS -->
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <GlobeAltIcon class="w-5 h-5 text-gray-500" />
                                        <span class="text-sm font-semibold text-gray-700">Coordonnées GPS (optionnel)</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Latitude</label>
                                            <input
                                                v-model="form.latitude"
                                                type="number"
                                                step="any"
                                                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 transition-all text-sm"
                                                placeholder="ex: 48.8566"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Longitude</label>
                                            <input
                                                v-model="form.longitude"
                                                type="number"
                                                step="any"
                                                class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/10 transition-all text-sm"
                                                placeholder="ex: 2.3522"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Contact & Récapitulatif -->
                        <div v-if="currentStep === 3" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <CheckCircleIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Contact & Récapitulatif</h3>
                                    <p class="text-sm text-gray-500">Coordonnées et vérification finale</p>
                                </div>
                            </div>

                            <!-- Contact -->
                            <div class="space-y-6 mb-8">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Téléphone
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <PhoneIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <input
                                                v-model="form.phone"
                                                type="text"
                                                class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all"
                                                placeholder="+33 1 23 45 67 89"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Email
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <EnvelopeIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <input
                                                v-model="form.email"
                                                type="email"
                                                class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all"
                                                placeholder="site@example.com"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Récapitulatif -->
                            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6">
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                    <ClipboardDocumentListIcon class="w-5 h-5 mr-2 text-indigo-600" />
                                    Récapitulatif des modifications
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Info Site -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <BuildingOfficeIcon class="w-4 h-4 text-indigo-600" />
                                            </div>
                                            <span class="font-medium text-gray-700">Site</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p><span class="text-gray-500">Nom:</span> <span class="font-medium">{{ form.name || '-' }}</span></p>
                                            <p><span class="text-gray-500">Code:</span> <span class="font-mono font-medium">{{ form.code?.toUpperCase() || '-' }}</span></p>
                                            <p>
                                                <span class="text-gray-500">Statut:</span>
                                                <span :class="statusColor" class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium">
                                                    {{ statusLabel }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Adresse -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <MapPinIcon class="w-4 h-4 text-purple-600" />
                                            </div>
                                            <span class="font-medium text-gray-700">Adresse</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p class="font-medium">{{ form.address || '-' }}</p>
                                            <p>{{ form.postal_code }} {{ form.city }}</p>
                                            <p class="text-gray-500">{{ form.country || '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Summary -->
                                <div v-if="form.phone || form.email" class="mt-4 bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex items-center space-x-2 mb-3">
                                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                                            <EnvelopeIcon class="w-4 h-4 text-emerald-600" />
                                        </div>
                                        <span class="font-medium text-gray-700">Contact</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <p v-if="form.phone"><span class="text-gray-500">Tél:</span> {{ form.phone }}</p>
                                        <p v-if="form.email"><span class="text-gray-500">Email:</span> {{ form.email }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="inline-flex items-center px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-100 transition-all"
                            >
                                <ChevronLeftIcon class="w-5 h-5 mr-2" />
                                Précédent
                            </button>
                            <div v-else></div>

                            <div class="flex items-center space-x-3">
                                <Link
                                    :href="route('tenant.sites.show', site.id)"
                                    class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-medium transition-colors"
                                >
                                    Annuler
                                </Link>
                                <button
                                    v-if="currentStep < 3"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Suivant
                                    <ChevronRightIcon class="w-5 h-5 ml-2" />
                                </button>
                                <button
                                    v-else
                                    type="submit"
                                    :disabled="form.processing || !canProceed"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ArrowPathIcon v-if="form.processing" class="animate-spin w-5 h-5 mr-2" />
                                    <CheckIcon v-else class="w-5 h-5 mr-2" />
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    MapPinIcon,
    BuildingOfficeIcon,
    CheckIcon,
    CheckCircleIcon,
    PhoneIcon,
    EnvelopeIcon,
    GlobeAltIcon,
    ClipboardDocumentListIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ArrowPathIcon,
    NoSymbolIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    site: Object,
})

const currentStep = ref(1)
const stepErrors = ref({})

const steps = [
    { number: 1, title: 'Informations', icon: BuildingOfficeIcon },
    { number: 2, title: 'Adresse', icon: MapPinIcon },
    { number: 3, title: 'Finalisation', icon: CheckCircleIcon }
]

const statuses = [
    {
        value: 'active',
        label: 'Actif',
        icon: CheckCircleIcon,
        bgColor: 'bg-emerald-100',
        iconColor: 'text-emerald-600'
    },
    {
        value: 'inactive',
        label: 'Inactif',
        icon: NoSymbolIcon,
        bgColor: 'bg-gray-100',
        iconColor: 'text-gray-600'
    },
    {
        value: 'maintenance',
        label: 'Maintenance',
        icon: WrenchScrewdriverIcon,
        bgColor: 'bg-amber-100',
        iconColor: 'text-amber-600'
    }
]

const form = useForm({
    name: props.site.name,
    code: props.site.code,
    description: props.site.description || '',
    status: props.site.status,
    address: props.site.address,
    city: props.site.city,
    postal_code: props.site.postal_code,
    country: props.site.country,
    latitude: props.site.latitude,
    longitude: props.site.longitude,
    phone: props.site.phone || '',
    email: props.site.email || '',
})

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        return form.name && form.code && form.status
    }
    if (currentStep.value === 2) {
        return form.address && form.city && form.postal_code && form.country
    }
    return true
})

const statusLabel = computed(() => {
    const status = statuses.find(s => s.value === form.status)
    return status ? status.label : form.status
})

const statusColor = computed(() => {
    switch (form.status) {
        case 'active': return 'bg-emerald-100 text-emerald-700'
        case 'inactive': return 'bg-gray-100 text-gray-700'
        case 'maintenance': return 'bg-amber-100 text-amber-700'
        default: return 'bg-gray-100 text-gray-700'
    }
})

const validateStep = (step) => {
    const errors = {}
    switch (step) {
        case 1:
            if (!form.name) errors.name = 'Le nom du site est obligatoire'
            if (!form.code) errors.code = 'Le code du site est obligatoire'
            break
        case 2:
            if (!form.address) errors.address = 'L\'adresse est obligatoire'
            if (!form.city) errors.city = 'La ville est obligatoire'
            if (!form.postal_code) errors.postal_code = 'Le code postal est obligatoire'
            break
    }
    return errors
}

const nextStep = () => {
    const errors = validateStep(currentStep.value)
    stepErrors.value = errors
    if (Object.keys(errors).length > 0) {
        setTimeout(() => {
            const firstErrorField = document.querySelector('.field-error')
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
            }
        }, 100)
        return
    }
    if (currentStep.value < 3) {
        currentStep.value++
        stepErrors.value = {}
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
        stepErrors.value = {}
    }
}

const submit = () => {
    form.put(route('tenant.sites.update', props.site.id))
}
</script>
