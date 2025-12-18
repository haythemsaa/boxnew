<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue'

const props = defineProps({
    accessCodes: Array,
    guestCodes: Array,
    canCreateGuestCodes: Boolean,
    maxGuests: Number,
    sites: Array,
    accessHistory: Array,
})

const activeTab = ref('codes') // 'codes' or 'history'

const showGuestModal = ref(false)
const showQRModal = ref(false)
const selectedCode = ref(null)
const copiedCode = ref(null)
const qrSvgContent = ref('')

const guestForm = useForm({
    site_id: '',
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    valid_from: '',
    valid_until: '',
    purpose: '',
})

const createGuestCode = () => {
    guestForm.post(route('customer.portal.access.guest-code.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showGuestModal.value = false
            guestForm.reset()
        }
    })
}

const showQR = (code) => {
    selectedCode.value = code
    showQRModal.value = true
}

const copyToClipboard = async (text, codeId) => {
    try {
        await navigator.clipboard.writeText(text)
        copiedCode.value = codeId
        setTimeout(() => {
            copiedCode.value = null
        }, 2000)
    } catch (e) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea')
        textArea.value = text
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand('copy')
        document.body.removeChild(textArea)
        copiedCode.value = codeId
        setTimeout(() => {
            copiedCode.value = null
        }, 2000)
    }
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const statusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        suspended: 'bg-yellow-100 text-yellow-800',
        expired: 'bg-gray-100 text-gray-800',
        revoked: 'bg-red-100 text-red-800',
        pending: 'bg-blue-100 text-blue-800',
        used: 'bg-purple-100 text-purple-800',
        cancelled: 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const statusLabel = (status) => {
    const labels = {
        active: 'Actif',
        suspended: 'Suspendu',
        expired: 'Expiré',
        revoked: 'Révoqué',
        pending: 'En attente',
        used: 'Utilisé',
        cancelled: 'Annulé',
        granted: 'Accordé',
        denied: 'Refusé',
        success: 'Succès',
        failed: 'Échoué'
    }
    return labels[status] || status
}

const accessMethodLabel = (method) => {
    const labels = {
        pin: 'Code PIN',
        qr_code: 'QR Code',
        rfid: 'Badge RFID',
        app: 'Application',
        manual: 'Manuel',
        guest: 'Invité',
        biometric: 'Biométrique'
    }
    return labels[method] || method
}

const accessStatusColor = (status) => {
    const colors = {
        granted: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        success: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        denied: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    }
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

// Generate QR code URL (using a public QR code API)
const getQRCodeUrl = (code) => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(code)}&format=png&ecc=H`
}

// Download QR code as image
const downloadQRCode = async () => {
    if (!selectedCode.value) return

    try {
        const response = await fetch(getQRCodeUrl(selectedCode.value.qr_code))
        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `qrcode-boxibox-${selectedCode.value.site?.name || 'access'}.png`
        document.body.appendChild(a)
        a.click()
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)
    } catch (e) {
        // Fallback: open in new tab
        window.open(getQRCodeUrl(selectedCode.value.qr_code), '_blank')
    }
}
</script>

<template>
    <Head title="Mes codes d'accès" />

    <CustomerPortalLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes codes d'accès</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Utilisez ces codes pour accéder à vos espaces de stockage</p>
                </div>

                <!-- Tab Navigation -->
                <div class="flex gap-2 mb-6 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                    <button
                        @click="activeTab = 'codes'"
                        :class="[
                            'flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2',
                            activeTab === 'codes'
                                ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        Codes d'accès
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        :class="[
                            'flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 flex items-center justify-center gap-2',
                            activeTab === 'history'
                                ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Historique
                        <span v-if="accessHistory?.length" class="px-2 py-0.5 text-xs bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full">
                            {{ accessHistory.length }}
                        </span>
                    </button>
                </div>

                <!-- Tab: Codes d'accès -->
                <div v-show="activeTab === 'codes'">
                <!-- My Access Codes -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Mes codes personnels</h2>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="code in accessCodes" :key="code.id" class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-lg font-medium text-gray-900 dark:text-white">{{ code.site?.name }}</span>
                                        <span :class="['px-2 py-1 text-xs rounded-full', statusColor(code.status)]">
                                            {{ statusLabel(code.status) }}
                                        </span>
                                        <span v-if="code.is_master" class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">
                                            Principal
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        <!-- PIN Code -->
                                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border border-indigo-100">
                                            <p class="text-sm text-indigo-600 font-medium mb-2">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                                Code PIN
                                            </p>
                                            <div class="flex items-center gap-3">
                                                <code class="text-3xl font-mono tracking-[0.3em] text-gray-900 font-bold">
                                                    {{ code.access_code }}
                                                </code>
                                                <button
                                                    @click="copyToClipboard(code.access_code, 'pin-' + code.id)"
                                                    :class="[
                                                        'p-2.5 rounded-lg transition-all duration-200',
                                                        copiedCode === 'pin-' + code.id
                                                            ? 'bg-green-500 text-white'
                                                            : 'bg-white text-indigo-600 hover:bg-indigo-100 shadow-sm'
                                                    ]"
                                                    :title="copiedCode === 'pin-' + code.id ? 'Copié !' : 'Copier le code'"
                                                >
                                                    <svg v-if="copiedCode !== 'pin-' + code.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- QR Code -->
                                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-4 border border-emerald-100">
                                            <p class="text-sm text-emerald-600 font-medium mb-2">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                                QR Code
                                            </p>
                                            <button
                                                @click="showQR(code)"
                                                class="w-full flex items-center justify-center gap-3 py-3 bg-white text-emerald-700 rounded-lg hover:bg-emerald-100 transition shadow-sm border border-emerald-200"
                                            >
                                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                                <span class="font-medium">Scanner pour entrer</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                        <span v-if="code.is_permanent">Validité: Permanent</span>
                                        <span v-else>Validité: {{ formatDate(code.valid_from) }} - {{ formatDate(code.valid_until) }}</span>
                                        <span v-if="code.max_uses" class="ml-4">
                                            Utilisations: {{ code.use_count }} / {{ code.max_uses }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="accessCodes.length === 0" class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <p>Aucun code d'accès actif</p>
                            <p class="text-sm mt-1">Les codes sont générés automatiquement avec votre contrat</p>
                        </div>
                    </div>
                </div>

                <!-- Guest Codes -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Codes invités</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Créez des codes temporaires pour vos visiteurs</p>
                        </div>
                        <button
                            v-if="canCreateGuestCodes"
                            @click="showGuestModal = true"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Inviter quelqu'un
                        </button>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="guest in guestCodes" :key="guest.id" class="p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ guest.guest_name }}</span>
                                        <span :class="['px-2 py-1 text-xs rounded-full', statusColor(guest.status)]">
                                            {{ statusLabel(guest.status) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ guest.site?.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ formatDate(guest.valid_from) }} - {{ formatDate(guest.valid_until) }}
                                    </p>
                                    <p v-if="guest.purpose" class="text-sm text-gray-400 dark:text-gray-500 mt-1">{{ guest.purpose }}</p>
                                </div>
                                <div class="text-right">
                                    <code class="px-3 py-1 bg-gray-100 dark:bg-gray-700 dark:text-gray-200 rounded font-mono">{{ guest.access_code }}</code>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ guest.use_count }} / {{ guest.max_uses }} utilisations</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="guestCodes.length === 0" class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <p>Aucun code invité</p>
                            <p v-if="canCreateGuestCodes" class="text-sm mt-1">Créez un code pour permettre à quelqu'un d'accéder au site</p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-300">
                            <p class="font-medium mb-1">Comment utiliser vos codes d'accès ?</p>
                            <ul class="list-disc ml-4 space-y-1">
                                <li>Entrez votre code PIN sur le clavier à l'entrée du site</li>
                                <li>Ou scannez le QR code sur la borne d'accès</li>
                                <li>Les codes invités sont à usage limité et temporaire</li>
                            </ul>
                        </div>
                    </div>
                </div>
                </div><!-- End Tab: Codes d'accès -->

                <!-- Tab: Historique d'accès -->
                <div v-show="activeTab === 'history'">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Historique des accès</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Vos 50 dernières entrées/sorties</p>
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div
                                v-for="log in accessHistory"
                                :key="log.id"
                                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <!-- Status Icon -->
                                        <div :class="[
                                            'w-10 h-10 rounded-full flex items-center justify-center',
                                            log.status === 'granted' || log.status === 'success'
                                                ? 'bg-green-100 dark:bg-green-900/30'
                                                : 'bg-red-100 dark:bg-red-900/30'
                                        ]">
                                            <svg v-if="log.status === 'granted' || log.status === 'success'" class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>

                                        <!-- Details -->
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-900 dark:text-white">{{ log.site_name }}</span>
                                                <span v-if="log.box_name !== '-'" class="text-sm text-gray-500 dark:text-gray-400">
                                                    • Box {{ log.box_name }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ log.accessed_at }}
                                                </span>
                                                <span v-if="log.gate_name" class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4" />
                                                    </svg>
                                                    {{ log.gate_name }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Method & Status -->
                                    <div class="text-right">
                                        <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', accessStatusColor(log.status)]">
                                            {{ statusLabel(log.status) }}
                                        </span>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ accessMethodLabel(log.access_method) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="!accessHistory?.length" class="p-12 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucun historique d'accès</h3>
                                <p class="text-gray-500 dark:text-gray-400">Vos entrées et sorties apparaîtront ici</p>
                            </div>
                        </div>
                    </div>
                </div><!-- End Tab: Historique -->
            </div>
        </div>

        <!-- QR Code Modal - Mobile Optimized -->
        <Teleport to="body">
            <div v-if="showQRModal && selectedCode" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 py-6">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showQRModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-5">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold">Code d'accès</h3>
                                    <p class="text-indigo-200 text-sm mt-1">{{ selectedCode.site?.name }}</p>
                                </div>
                                <button
                                    @click="showQRModal = false"
                                    class="p-2 hover:bg-white/20 rounded-full transition"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- QR Code Display -->
                        <div class="p-6 text-center">
                            <div class="bg-white p-6 rounded-2xl shadow-inner border-2 border-gray-100 inline-block">
                                <img
                                    :src="getQRCodeUrl(selectedCode.qr_code)"
                                    alt="QR Code"
                                    class="w-56 h-56 mx-auto"
                                    loading="lazy"
                                >
                            </div>

                            <!-- PIN Code Display -->
                            <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                                <p class="text-sm text-gray-500 mb-2">Ou utilisez le code PIN</p>
                                <div class="flex items-center justify-center gap-3">
                                    <code class="text-3xl font-mono tracking-[0.3em] text-gray-900 font-bold">
                                        {{ selectedCode.access_code }}
                                    </code>
                                    <button
                                        @click="copyToClipboard(selectedCode.access_code, 'modal-pin')"
                                        :class="[
                                            'p-2 rounded-lg transition-all duration-200',
                                            copiedCode === 'modal-pin'
                                                ? 'bg-green-500 text-white'
                                                : 'bg-indigo-100 text-indigo-600 hover:bg-indigo-200'
                                        ]"
                                    >
                                        <svg v-if="copiedCode !== 'modal-pin'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Instructions -->
                            <div class="mt-6 text-sm text-gray-600 space-y-2">
                                <div class="flex items-center gap-2 justify-center">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Présentez ce QR code à la borne d'entrée</span>
                                </div>
                                <div class="flex items-center gap-2 justify-center text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span v-if="selectedCode.is_permanent">Validité: Permanent</span>
                                    <span v-else>Valide jusqu'au {{ formatDate(selectedCode.valid_until) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="p-4 bg-gray-50 border-t border-gray-100 flex gap-3">
                            <button
                                @click="downloadQRCode"
                                class="flex-1 px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition flex items-center justify-center gap-2 font-medium"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Télécharger
                            </button>
                            <button
                                @click="showQRModal = false"
                                class="flex-1 px-4 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition font-medium"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Guest Code Modal -->
        <Teleport to="body">
            <div v-if="showGuestModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black opacity-50" @click="showGuestModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Créer un code invité</h3>

                        <form @submit.prevent="createGuestCode" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                                <select
                                    v-model="guestForm.site_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Sélectionner...</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id">
                                        {{ site.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'invité</label>
                                <input
                                    type="text"
                                    v-model="guestForm.guest_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                >
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (optionnel)</label>
                                    <input
                                        type="email"
                                        v-model="guestForm.guest_email"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone (optionnel)</label>
                                    <input
                                        type="tel"
                                        v-model="guestForm.guest_phone"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                                    <input
                                        type="datetime-local"
                                        v-model="guestForm.valid_from"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        required
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                                    <input
                                        type="datetime-local"
                                        v-model="guestForm.valid_until"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        required
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Raison de la visite (optionnel)</label>
                                <input
                                    type="text"
                                    v-model="guestForm.purpose"
                                    placeholder="Ex: Récupération de cartons"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showGuestModal = false"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="guestForm.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                                >
                                    Créer le code
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </CustomerPortalLayout>
</template>
