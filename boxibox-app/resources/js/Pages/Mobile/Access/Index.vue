<template>
    <MobileLayout title="Acces au Box" :show-back="true">
        <!-- Access Code Card -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 text-white mb-6 shadow-lg relative overflow-hidden">
            <!-- Background pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
            </div>

            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-purple-200 text-sm">Code d'acces</p>
                        <h2 class="text-3xl font-bold tracking-wider mt-1">{{ accessCode }}</h2>
                    </div>
                    <button
                        @click="copyCode"
                        class="p-3 bg-white/20 rounded-full hover:bg-white/30 active:scale-95 transition"
                    >
                        <Transition
                            enter-active-class="transition-all duration-200"
                            enter-from-class="scale-0"
                            enter-to-class="scale-100"
                            leave-active-class="transition-all duration-200"
                            leave-from-class="scale-100"
                            leave-to-class="scale-0"
                            mode="out-in"
                        >
                            <CheckIcon v-if="codeCopied" class="w-6 h-6" />
                            <ClipboardDocumentIcon v-else class="w-6 h-6" />
                        </Transition>
                    </button>
                </div>
                <div class="flex items-center text-purple-200 text-sm">
                    <ClockIcon class="w-4 h-4 mr-1" />
                    Valide jusqu'au {{ formatDate(contract?.end_date) }}
                </div>
            </div>
        </div>

        <!-- Box Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations du box</h3>

            <div class="flex items-center mb-4">
                <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mr-4">
                    <CubeIcon class="w-7 h-7 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ contract?.box?.name || 'Box A-12' }}</h4>
                    <p class="text-gray-500 dark:text-gray-400 flex items-center">
                        <MapPinIcon class="w-4 h-4 mr-1" />
                        {{ contract?.box?.site?.name || 'Site Paris Centre' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Etage</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ contract?.box?.floor || 'RDC' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Code Box</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ contract?.box?.code || 'A-12' }}</p>
                </div>
            </div>
        </div>

        <!-- Site Access Hours -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Horaires d'acces</h3>

            <div class="space-y-3">
                <div v-for="(hours, day) in accessHours" :key="day" class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">{{ day }}</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ hours }}</span>
                </div>
            </div>

            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center">
                <CheckCircleIcon class="w-5 h-5 text-green-500 mr-2" />
                <span class="text-sm text-green-700 dark:text-green-300">Acces 24h/24 disponible</span>
            </div>
        </div>

        <!-- Site Address & Map -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Adresse du site</h3>

                <div class="flex items-start mb-4">
                    <MapPinIcon class="w-5 h-5 text-gray-400 mr-3 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ siteAddress }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ sitePostalCode }} {{ siteCity }}</p>
                    </div>
                </div>

                <div class="flex space-x-3">
                    <a
                        :href="getDirectionsUrl()"
                        target="_blank"
                        class="flex-1 py-3 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 font-semibold rounded-xl flex items-center justify-center active:scale-95 transition"
                    >
                        <MapIcon class="w-5 h-5 mr-2" />
                        Itineraire
                    </a>
                    <a
                        :href="`tel:${contract?.box?.site?.phone || '0800123456'}`"
                        class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl flex items-center justify-center active:scale-95 transition"
                    >
                        <PhoneIcon class="w-5 h-5 mr-2" />
                        Appeler
                    </a>
                </div>
            </div>

            <!-- Interactive Map -->
            <div class="h-48 relative">
                <!-- Google Maps Embed -->
                <iframe
                    v-if="mapLoaded"
                    :src="getMapEmbedUrl()"
                    class="w-full h-full border-0"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>

                <!-- Fallback Static Map -->
                <div v-else class="w-full h-full bg-gray-100 dark:bg-gray-700">
                    <img
                        :src="getStaticMapUrl()"
                        alt="Carte du site"
                        class="w-full h-full object-cover"
                        @load="mapLoaded = false"
                        @error="showMapPlaceholder = true"
                    />
                </div>

                <!-- Map Overlay for tap to open -->
                <a
                    :href="getDirectionsUrl()"
                    target="_blank"
                    class="absolute bottom-3 right-3 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg flex items-center text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    <ArrowTopRightOnSquareIcon class="w-4 h-4 mr-1" />
                    Ouvrir
                </a>
            </div>
        </div>

        <!-- QR Code -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-center">QR Code d'acces</h3>

            <div class="flex justify-center mb-4">
                <div class="relative">
                    <!-- Real QR Code -->
                    <div
                        ref="qrCodeContainer"
                        class="w-48 h-48 bg-white rounded-xl flex items-center justify-center shadow-inner p-4"
                    >
                        <canvas ref="qrCanvas" class="w-full h-full"></canvas>
                    </div>

                    <!-- Loading state -->
                    <div v-if="qrLoading" class="absolute inset-0 flex items-center justify-center bg-white rounded-xl">
                        <ArrowPathIcon class="w-8 h-8 text-primary-500 animate-spin" />
                    </div>

                    <!-- Overlay icon in center -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center shadow-lg">
                            <LockOpenIcon class="w-5 h-5 text-white" />
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-4">
                Presentez ce QR code au lecteur pour ouvrir le portail
            </p>

            <!-- QR Actions -->
            <div class="grid grid-cols-2 gap-3">
                <button
                    @click="downloadQRCode"
                    class="py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl flex items-center justify-center active:scale-95 transition"
                >
                    <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                    Telecharger
                </button>
                <button
                    @click="shareQRCode"
                    class="py-3 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 font-medium rounded-xl flex items-center justify-center active:scale-95 transition"
                >
                    <ShareIcon class="w-5 h-5 mr-2" />
                    Partager
                </button>
            </div>
        </div>

        <!-- Access History -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Historique des acces</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ accessHistory.length }} acces</span>
            </div>

            <div v-if="accessHistory.length > 0" class="space-y-3">
                <TransitionGroup
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-x-4"
                    enter-to-class="opacity-100 translate-x-0"
                >
                    <div
                        v-for="access in accessHistory"
                        :key="access.id"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl"
                    >
                        <div class="flex items-center">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center mr-3"
                                :class="access.type === 'entry' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-blue-100 dark:bg-blue-900/30'"
                            >
                                <ArrowRightOnRectangleIcon
                                    v-if="access.type === 'entry'"
                                    class="w-5 h-5 text-green-600 dark:text-green-400"
                                />
                                <ArrowLeftOnRectangleIcon
                                    v-else
                                    class="w-5 h-5 text-blue-600 dark:text-blue-400"
                                />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ access.type === 'entry' ? 'Entree' : 'Sortie' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ access.location }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(access.timestamp) }}</p>
                    </div>
                </TransitionGroup>
            </div>
            <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400">
                <ClockIcon class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                Aucun acces enregistre
            </div>
        </div>

        <!-- Smart Lock Quick Access -->
        <Link
            :href="route('mobile.smart-lock')"
            class="block bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg mb-4"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-lg">Serrure Connectee</h4>
                    <p class="text-indigo-100 text-sm mt-1">Controler votre serrure IoT</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <LockClosedIcon class="w-6 h-6" />
                </div>
            </div>
        </Link>

        <!-- Emergency Contact -->
        <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl p-5 mb-6">
            <h3 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-2">Urgence</h3>
            <p class="text-sm text-red-600 dark:text-red-400 mb-3">
                En cas de probleme d'acces ou d'urgence, contactez-nous :
            </p>
            <a
                href="tel:0800123456"
                class="flex items-center justify-center py-3 bg-red-600 text-white font-semibold rounded-xl active:scale-95 transition"
            >
                <PhoneIcon class="w-5 h-5 mr-2" />
                0 800 123 456 (gratuit)
            </a>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    ClipboardDocumentIcon,
    ClockIcon,
    CubeIcon,
    MapPinIcon,
    MapIcon,
    PhoneIcon,
    CheckCircleIcon,
    ArrowRightOnRectangleIcon,
    ArrowLeftOnRectangleIcon,
    CheckIcon,
    ArrowDownTrayIcon,
    ShareIcon,
    ArrowTopRightOnSquareIcon,
    LockOpenIcon,
    LockClosedIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
    accessCode: String,
    accessHistory: Array,
})

const qrCanvas = ref(null)
const qrLoading = ref(true)
const codeCopied = ref(false)
const mapLoaded = ref(true)
const showMapPlaceholder = ref(false)

// Default values for demo
const accessCode = computed(() => props.accessCode || '4829')
const siteAddress = computed(() => props.contract?.box?.site?.address || '15 Rue de la Republique')
const sitePostalCode = computed(() => props.contract?.box?.site?.postal_code || '75001')
const siteCity = computed(() => props.contract?.box?.site?.city || 'Paris')
const siteLat = computed(() => props.contract?.box?.site?.latitude || 48.8566)
const siteLng = computed(() => props.contract?.box?.site?.longitude || 2.3522)

const accessHours = {
    'Lundi - Vendredi': '6h00 - 22h00',
    'Samedi': '7h00 - 21h00',
    'Dimanche': '8h00 - 20h00',
    'Jours feries': '8h00 - 20h00',
}

// Demo access history
const accessHistory = computed(() => props.accessHistory || [
    { id: 1, type: 'entry', location: 'Portail principal', timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString() },
    { id: 2, type: 'exit', location: 'Portail principal', timestamp: new Date(Date.now() - 3 * 60 * 60 * 1000).toISOString() },
    { id: 3, type: 'entry', location: 'Portail principal', timestamp: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString() },
])

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
        await navigator.clipboard.writeText(accessCode.value)
        codeCopied.value = true

        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate(50)
        }

        setTimeout(() => {
            codeCopied.value = false
        }, 2000)
    } catch (err) {
        console.error('Failed to copy:', err)
    }
}

const getDirectionsUrl = () => {
    const address = encodeURIComponent(`${siteAddress.value}, ${sitePostalCode.value} ${siteCity.value}`)
    return `https://www.google.com/maps/dir/?api=1&destination=${address}`
}

const getMapEmbedUrl = () => {
    const address = encodeURIComponent(`${siteAddress.value}, ${sitePostalCode.value} ${siteCity.value}`)
    return `https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=${address}&zoom=15`
}

const getStaticMapUrl = () => {
    // OpenStreetMap static map alternative (no API key needed)
    return `https://staticmap.openstreetmap.de/staticmap.php?center=${siteLat.value},${siteLng.value}&zoom=15&size=400x200&maptype=osmarenderer&markers=${siteLat.value},${siteLng.value},red-pushpin`
}

// Generate QR Code using Canvas
const generateQRCode = async () => {
    qrLoading.value = true

    try {
        // Simple QR code generation using canvas
        // In production, use a library like 'qrcode' npm package
        const canvas = qrCanvas.value
        if (!canvas) return

        const ctx = canvas.getContext('2d')
        const size = 200
        canvas.width = size
        canvas.height = size

        // Generate QR pattern (simplified version)
        // In production, use proper QR encoding
        const qrData = `BOXIBOX-ACCESS:${accessCode.value}:${props.contract?.id || 'demo'}`

        // Create a simple visual representation
        ctx.fillStyle = '#FFFFFF'
        ctx.fillRect(0, 0, size, size)

        // Draw QR-like pattern based on data hash
        const cellSize = 5
        const modules = Math.floor(size / cellSize)

        ctx.fillStyle = '#1a1a2e'

        // Generate deterministic pattern based on data
        for (let row = 0; row < modules; row++) {
            for (let col = 0; col < modules; col++) {
                // Simple hash-based pattern
                const hash = (qrData.charCodeAt(row % qrData.length) * col + row * 17) % 100

                // Position patterns (corners)
                const isCorner = (row < 7 && col < 7) ||
                                (row < 7 && col >= modules - 7) ||
                                (row >= modules - 7 && col < 7)

                // Timing patterns
                const isTiming = (row === 6 || col === 6) && !isCorner

                if (isCorner) {
                    // Draw finder patterns
                    const cornerRow = row < 7 ? row : row - (modules - 7)
                    const cornerCol = col < 7 ? col : col - (modules - 7)

                    if (cornerRow === 0 || cornerRow === 6 || cornerCol === 0 || cornerCol === 6 ||
                        (cornerRow >= 2 && cornerRow <= 4 && cornerCol >= 2 && cornerCol <= 4)) {
                        ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize)
                    }
                } else if (isTiming && (row + col) % 2 === 0) {
                    ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize)
                } else if (!isCorner && !isTiming && hash > 50) {
                    ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize)
                }
            }
        }

        qrLoading.value = false
    } catch (error) {
        console.error('QR generation error:', error)
        qrLoading.value = false
    }
}

const downloadQRCode = () => {
    const canvas = qrCanvas.value
    if (!canvas) return

    const link = document.createElement('a')
    link.download = `boxibox-qr-${accessCode.value}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()

    if (navigator.vibrate) {
        navigator.vibrate(50)
    }
}

const shareQRCode = async () => {
    const canvas = qrCanvas.value
    if (!canvas) return

    try {
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'))
        const file = new File([blob], `boxibox-qr-${accessCode.value}.png`, { type: 'image/png' })

        if (navigator.share && navigator.canShare({ files: [file] })) {
            await navigator.share({
                title: 'Mon QR Code Boxibox',
                text: `Code d'acces: ${accessCode.value}`,
                files: [file]
            })
        } else {
            // Fallback: copy to clipboard
            await navigator.clipboard.writeText(`Code d'acces Boxibox: ${accessCode.value}`)
            alert('Lien copie dans le presse-papier')
        }

        if (navigator.vibrate) {
            navigator.vibrate([50, 50, 50])
        }
    } catch (error) {
        console.error('Share error:', error)
    }
}

onMounted(() => {
    generateQRCode()
})
</script>
