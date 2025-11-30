<template>
    <MobileLayout title="Acces au Box" :show-back="true">
        <!-- Access Code Card -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 text-white mb-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-purple-200 text-sm">Code d'acces</p>
                    <h2 class="text-3xl font-bold tracking-wider mt-1">{{ accessCode }}</h2>
                </div>
                <button
                    @click="copyCode"
                    class="p-3 bg-white/20 rounded-full hover:bg-white/30 transition"
                >
                    <ClipboardDocumentIcon class="w-6 h-6" />
                </button>
            </div>
            <div class="flex items-center text-purple-200 text-sm">
                <ClockIcon class="w-4 h-4 mr-1" />
                Valide jusqu'au {{ formatDate(contract?.end_date) }}
            </div>
        </div>

        <!-- Box Info -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du box</h3>

            <div class="flex items-center mb-4">
                <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mr-4">
                    <CubeIcon class="w-7 h-7 text-primary-600" />
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-lg">{{ contract?.box?.name }}</h4>
                    <p class="text-gray-500 flex items-center">
                        <MapPinIcon class="w-4 h-4 mr-1" />
                        {{ contract?.box?.site?.name }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="text-xs text-gray-500 mb-1">Etage</p>
                    <p class="font-semibold text-gray-900">{{ contract?.box?.floor || 'RDC' }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="text-xs text-gray-500 mb-1">Code Box</p>
                    <p class="font-semibold text-gray-900">{{ contract?.box?.code }}</p>
                </div>
            </div>
        </div>

        <!-- Site Access Hours -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Horaires d'acces</h3>

            <div class="space-y-3">
                <div v-for="(hours, day) in accessHours" :key="day" class="flex justify-between items-center">
                    <span class="text-gray-600">{{ day }}</span>
                    <span class="font-medium text-gray-900">{{ hours }}</span>
                </div>
            </div>

            <div class="mt-4 p-3 bg-green-50 rounded-xl flex items-center">
                <CheckCircleIcon class="w-5 h-5 text-green-500 mr-2" />
                <span class="text-sm text-green-700">Acces 24h/24 disponible</span>
            </div>
        </div>

        <!-- Site Address & Map -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Adresse du site</h3>

                <div class="flex items-start mb-4">
                    <MapPinIcon class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900">{{ contract?.box?.site?.address }}</p>
                        <p class="text-gray-500">{{ contract?.box?.site?.postal_code }} {{ contract?.box?.site?.city }}</p>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <a
                        :href="getDirectionsUrl()"
                        target="_blank"
                        class="flex-1 py-3 bg-primary-50 text-primary-600 font-semibold rounded-xl flex items-center justify-center"
                    >
                        <MapIcon class="w-5 h-5 mr-2" />
                        Itineraire
                    </a>
                    <a
                        :href="`tel:${contract?.box?.site?.phone}`"
                        class="flex-1 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl flex items-center justify-center"
                    >
                        <PhoneIcon class="w-5 h-5 mr-2" />
                        Appeler
                    </a>
                </div>
            </div>

            <!-- Map Placeholder -->
            <div class="h-48 bg-gray-200 relative">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <MapPinIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                        <p class="text-gray-500 text-sm">Carte interactive</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">QR Code d'acces</h3>

            <div class="flex justify-center mb-4">
                <div class="w-48 h-48 bg-gray-100 rounded-xl flex items-center justify-center border-2 border-dashed border-gray-300">
                    <div class="text-center">
                        <QrCodeIcon class="w-16 h-16 text-gray-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-500">QR Code</p>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-500 text-center">
                Presentez ce QR code au lecteur pour ouvrir le portail
            </p>
        </div>

        <!-- Access History -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Historique des acces</h3>

            <div v-if="accessHistory.length > 0" class="space-y-3">
                <div
                    v-for="access in accessHistory"
                    :key="access.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                >
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center mr-3"
                            :class="access.type === 'entry' ? 'bg-green-100' : 'bg-blue-100'"
                        >
                            <ArrowRightOnRectangleIcon
                                v-if="access.type === 'entry'"
                                class="w-5 h-5 text-green-600"
                            />
                            <ArrowLeftOnRectangleIcon
                                v-else
                                class="w-5 h-5 text-blue-600"
                            />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ access.type === 'entry' ? 'Entree' : 'Sortie' }}</p>
                            <p class="text-sm text-gray-500">{{ access.location }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">{{ formatDateTime(access.timestamp) }}</p>
                </div>
            </div>
            <div v-else class="text-center py-6 text-gray-500">
                Aucun acces enregistre
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="bg-red-50 rounded-2xl p-5 mb-6">
            <h3 class="text-lg font-semibold text-red-800 mb-2">Urgence</h3>
            <p class="text-sm text-red-600 mb-3">
                En cas de probleme d'acces ou d'urgence, contactez-nous :
            </p>
            <a
                href="tel:0800123456"
                class="flex items-center justify-center py-3 bg-red-600 text-white font-semibold rounded-xl"
            >
                <PhoneIcon class="w-5 h-5 mr-2" />
                0 800 123 456 (gratuit)
            </a>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref } from 'vue'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    ClipboardDocumentIcon,
    ClockIcon,
    CubeIcon,
    MapPinIcon,
    MapIcon,
    PhoneIcon,
    CheckCircleIcon,
    QrCodeIcon,
    ArrowRightOnRectangleIcon,
    ArrowLeftOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
    accessCode: String,
    accessHistory: Array,
})

const accessHours = {
    'Lundi - Vendredi': '6h00 - 22h00',
    'Samedi': '7h00 - 21h00',
    'Dimanche': '8h00 - 20h00',
    'Jours feries': '8h00 - 20h00',
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(props.accessCode)
        alert('Code copie!')
    } catch (err) {
        console.error('Failed to copy:', err)
    }
}

const getDirectionsUrl = () => {
    const address = encodeURIComponent(
        `${props.contract?.box?.site?.address}, ${props.contract?.box?.site?.postal_code} ${props.contract?.box?.site?.city}`
    )
    return `https://www.google.com/maps/dir/?api=1&destination=${address}`
}
</script>
