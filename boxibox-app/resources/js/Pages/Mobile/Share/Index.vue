<template>
    <MobileLayout title="Partager l'Acces" :show-back="true">
        <!-- Current Shares -->
        <div v-if="activeShares.length > 0" class="mb-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Acces actifs</h3>
            <div class="space-y-3">
                <div
                    v-for="share in activeShares"
                    :key="share.id"
                    class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/20">
                                <UserIcon class="w-6 h-6 text-white" />
                            </div>
                            <div class="ml-3">
                                <p class="font-semibold text-gray-900">{{ share.name }}</p>
                                <p class="text-sm text-gray-500">{{ share.email || share.phone }}</p>
                            </div>
                        </div>
                        <button
                            @click="revokeShare(share)"
                            class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <div class="flex items-center text-gray-500">
                            <ClockIcon class="w-4 h-4 mr-1" />
                            <span>Expire {{ formatExpiry(share.expires_at) }}</span>
                        </div>
                        <span
                            class="px-2 py-1 rounded-full text-xs font-medium"
                            :class="share.used ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                        >
                            {{ share.used ? 'Utilise' : 'En attente' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create New Share -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Nouvel acces temporaire</h3>
                <p class="text-sm text-gray-500 mt-1">Invitez quelqu'un a acceder a votre box</p>
            </div>

            <form @submit.prevent="createShare" class="p-5 space-y-4">
                <!-- Recipient Info -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Destinataire
                    </label>
                    <div class="relative">
                        <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Nom du destinataire"
                            class="w-full pl-10 pr-4 py-3 bg-gray-50 rounded-xl border-0 focus:ring-2 focus:ring-primary-500 focus:bg-white transition"
                            required
                        />
                    </div>
                </div>

                <!-- Contact Method -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Envoyer par
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            @click="contactMethod = 'email'"
                            class="p-3 rounded-xl border-2 transition flex items-center justify-center gap-2"
                            :class="contactMethod === 'email'
                                ? 'border-primary-500 bg-primary-50 text-primary-700'
                                : 'border-gray-200 text-gray-600'"
                        >
                            <EnvelopeIcon class="w-5 h-5" />
                            <span class="font-medium">Email</span>
                        </button>
                        <button
                            type="button"
                            @click="contactMethod = 'sms'"
                            class="p-3 rounded-xl border-2 transition flex items-center justify-center gap-2"
                            :class="contactMethod === 'sms'
                                ? 'border-primary-500 bg-primary-50 text-primary-700'
                                : 'border-gray-200 text-gray-600'"
                        >
                            <DevicePhoneMobileIcon class="w-5 h-5" />
                            <span class="font-medium">SMS</span>
                        </button>
                    </div>
                </div>

                <!-- Email or Phone -->
                <div>
                    <div class="relative">
                        <component
                            :is="contactMethod === 'email' ? EnvelopeIcon : DevicePhoneMobileIcon"
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
                        />
                        <input
                            v-model="form.contact"
                            :type="contactMethod === 'email' ? 'email' : 'tel'"
                            :placeholder="contactMethod === 'email' ? 'email@exemple.com' : '06 12 34 56 78'"
                            class="w-full pl-10 pr-4 py-3 bg-gray-50 rounded-xl border-0 focus:ring-2 focus:ring-primary-500 focus:bg-white transition"
                            required
                        />
                    </div>
                </div>

                <!-- Duration -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Duree de l'acces
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="option in durationOptions"
                            :key="option.value"
                            type="button"
                            @click="form.duration = option.value"
                            class="p-3 rounded-xl border-2 transition text-center"
                            :class="form.duration === option.value
                                ? 'border-primary-500 bg-primary-50 text-primary-700'
                                : 'border-gray-200 text-gray-600'"
                        >
                            <p class="font-bold text-lg">{{ option.number }}</p>
                            <p class="text-xs">{{ option.unit }}</p>
                        </button>
                    </div>
                </div>

                <!-- Access Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Type d'acces
                    </label>
                    <div class="space-y-2">
                        <label
                            v-for="accessType in accessTypes"
                            :key="accessType.value"
                            class="flex items-center p-3 rounded-xl border-2 cursor-pointer transition"
                            :class="form.accessType === accessType.value
                                ? 'border-primary-500 bg-primary-50'
                                : 'border-gray-200'"
                        >
                            <input
                                type="radio"
                                v-model="form.accessType"
                                :value="accessType.value"
                                class="sr-only"
                            />
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
                                :class="form.accessType === accessType.value ? 'bg-primary-500' : 'bg-gray-100'"
                            >
                                <component
                                    :is="accessType.icon"
                                    class="w-5 h-5"
                                    :class="form.accessType === accessType.value ? 'text-white' : 'text-gray-500'"
                                />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ accessType.label }}</p>
                                <p class="text-sm text-gray-500">{{ accessType.description }}</p>
                            </div>
                            <div
                                class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                :class="form.accessType === accessType.value
                                    ? 'border-primary-500 bg-primary-500'
                                    : 'border-gray-300'"
                            >
                                <CheckIcon v-if="form.accessType === accessType.value" class="w-3 h-3 text-white" />
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Message (optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Message (optionnel)
                    </label>
                    <textarea
                        v-model="form.message"
                        rows="3"
                        placeholder="Ajoutez un message personnel..."
                        class="w-full px-4 py-3 bg-gray-50 rounded-xl border-0 focus:ring-2 focus:ring-primary-500 focus:bg-white transition resize-none"
                    ></textarea>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="w-full py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl active:scale-[0.98] transition disabled:opacity-50"
                >
                    <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                        <ArrowPathIcon class="w-5 h-5 animate-spin" />
                        Envoi en cours...
                    </span>
                    <span v-else class="flex items-center justify-center gap-2">
                        <PaperAirplaneIcon class="w-5 h-5" />
                        Envoyer l'invitation
                    </span>
                </button>
            </form>
        </div>

        <!-- Success Modal -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showSuccess" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <Transition
                    enter-active-class="transition-all duration-300 delay-100"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div v-if="showSuccess" class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <CheckIcon class="w-8 h-8 text-green-600" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Invitation envoyee !</h3>
                        <p class="text-gray-600 mb-6">
                            {{ form.name }} recevra un {{ contactMethod === 'email' ? 'email' : 'SMS' }} avec les instructions d'acces.
                        </p>
                        <button
                            @click="closeSuccess"
                            class="w-full py-3 bg-primary-600 text-white font-semibold rounded-xl"
                        >
                            Compris
                        </button>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Info Card -->
        <div class="mt-6 bg-blue-50 rounded-2xl p-4">
            <div class="flex items-start">
                <InformationCircleIcon class="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" />
                <div>
                    <p class="font-medium text-blue-800">Comment ca marche ?</p>
                    <ul class="text-sm text-blue-700 mt-2 space-y-1">
                        <li>• Le destinataire recoit un lien securise</li>
                        <li>• Il peut acceder au site et a votre box</li>
                        <li>• L'acces expire automatiquement</li>
                        <li>• Vous pouvez revoquer l'acces a tout moment</li>
                    </ul>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    UserIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    ClockIcon,
    XMarkIcon,
    CheckIcon,
    PaperAirplaneIcon,
    ArrowPathIcon,
    InformationCircleIcon,
    LockOpenIcon,
    KeyIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
    shares: Array,
})

const contactMethod = ref('email')
const isSubmitting = ref(false)
const showSuccess = ref(false)

const form = reactive({
    name: '',
    contact: '',
    duration: '24h',
    accessType: 'full',
    message: '',
})

const activeShares = ref(props.shares || [
    {
        id: 1,
        name: 'Marie Dupont',
        email: 'marie@exemple.com',
        expires_at: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString(),
        used: true,
    },
])

const durationOptions = [
    { value: '1h', number: '1', unit: 'heure' },
    { value: '24h', number: '24', unit: 'heures' },
    { value: '7d', number: '7', unit: 'jours' },
    { value: '30d', number: '30', unit: 'jours' },
]

const accessTypes = [
    {
        value: 'full',
        label: 'Acces complet',
        description: 'Peut ouvrir le site et le box',
        icon: LockOpenIcon,
    },
    {
        value: 'site_only',
        label: 'Site uniquement',
        description: 'Peut acceder au site seulement',
        icon: KeyIcon,
    },
    {
        value: 'view_only',
        label: 'Consultation',
        description: 'Peut voir les infos sans deverrouiller',
        icon: EyeIcon,
    },
]

const formatExpiry = (date) => {
    const expiry = new Date(date)
    const now = new Date()
    const diff = expiry - now
    const hours = Math.floor(diff / (1000 * 60 * 60))
    const days = Math.floor(hours / 24)

    if (days > 0) {
        return `dans ${days} jour${days > 1 ? 's' : ''}`
    } else if (hours > 0) {
        return `dans ${hours} heure${hours > 1 ? 's' : ''}`
    } else {
        return 'bientot'
    }
}

const createShare = async () => {
    isSubmitting.value = true

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))

    // Add to active shares
    activeShares.value.unshift({
        id: Date.now(),
        name: form.name,
        [contactMethod.value === 'email' ? 'email' : 'phone']: form.contact,
        expires_at: getExpiryDate(form.duration),
        used: false,
    })

    isSubmitting.value = false
    showSuccess.value = true

    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate([50, 50, 100])
    }
}

const getExpiryDate = (duration) => {
    const now = new Date()
    switch (duration) {
        case '1h':
            return new Date(now.getTime() + 1 * 60 * 60 * 1000).toISOString()
        case '24h':
            return new Date(now.getTime() + 24 * 60 * 60 * 1000).toISOString()
        case '7d':
            return new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString()
        case '30d':
            return new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000).toISOString()
        default:
            return new Date(now.getTime() + 24 * 60 * 60 * 1000).toISOString()
    }
}

const closeSuccess = () => {
    showSuccess.value = false
    // Reset form
    form.name = ''
    form.contact = ''
    form.duration = '24h'
    form.accessType = 'full'
    form.message = ''
}

const revokeShare = async (share) => {
    if (confirm(`Revoquer l'acces de ${share.name} ?`)) {
        // Simulate API call
        activeShares.value = activeShares.value.filter(s => s.id !== share.id)

        if (navigator.vibrate) {
            navigator.vibrate(100)
        }
    }
}
</script>
