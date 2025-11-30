<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    signature: Object,
    contract: Object,
    customer: Object,
    tenant: Object,
    daysUntilExpiry: Number,
})

const canvas = ref(null)
const ctx = ref(null)
const isDrawing = ref(false)
const hasSignature = ref(false)
const signerName = ref('')
const acceptedTerms = ref(false)
const isSubmitting = ref(false)
const showRefuseModal = ref(false)
const refuseReason = ref('')
const error = ref('')

// Canvas setup
onMounted(() => {
    const canvasEl = canvas.value
    ctx.value = canvasEl.getContext('2d')

    // Set canvas size
    const rect = canvasEl.getBoundingClientRect()
    canvasEl.width = rect.width * 2
    canvasEl.height = rect.height * 2
    ctx.value.scale(2, 2)

    // Canvas style
    ctx.value.strokeStyle = '#1e40af'
    ctx.value.lineWidth = 2
    ctx.value.lineCap = 'round'
    ctx.value.lineJoin = 'round'

    // Fill white background
    ctx.value.fillStyle = '#ffffff'
    ctx.value.fillRect(0, 0, canvasEl.width, canvasEl.height)

    // Add event listeners
    canvasEl.addEventListener('mousedown', startDrawing)
    canvasEl.addEventListener('mousemove', draw)
    canvasEl.addEventListener('mouseup', stopDrawing)
    canvasEl.addEventListener('mouseout', stopDrawing)

    // Touch events
    canvasEl.addEventListener('touchstart', handleTouchStart)
    canvasEl.addEventListener('touchmove', handleTouchMove)
    canvasEl.addEventListener('touchend', stopDrawing)
})

onUnmounted(() => {
    const canvasEl = canvas.value
    if (canvasEl) {
        canvasEl.removeEventListener('mousedown', startDrawing)
        canvasEl.removeEventListener('mousemove', draw)
        canvasEl.removeEventListener('mouseup', stopDrawing)
        canvasEl.removeEventListener('mouseout', stopDrawing)
        canvasEl.removeEventListener('touchstart', handleTouchStart)
        canvasEl.removeEventListener('touchmove', handleTouchMove)
        canvasEl.removeEventListener('touchend', stopDrawing)
    }
})

const getMousePos = (e) => {
    const rect = canvas.value.getBoundingClientRect()
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    }
}

const getTouchPos = (e) => {
    const rect = canvas.value.getBoundingClientRect()
    const touch = e.touches[0]
    return {
        x: touch.clientX - rect.left,
        y: touch.clientY - rect.top
    }
}

const startDrawing = (e) => {
    isDrawing.value = true
    const pos = getMousePos(e)
    ctx.value.beginPath()
    ctx.value.moveTo(pos.x, pos.y)
}

const handleTouchStart = (e) => {
    e.preventDefault()
    isDrawing.value = true
    const pos = getTouchPos(e)
    ctx.value.beginPath()
    ctx.value.moveTo(pos.x, pos.y)
}

const draw = (e) => {
    if (!isDrawing.value) return
    const pos = getMousePos(e)
    ctx.value.lineTo(pos.x, pos.y)
    ctx.value.stroke()
    hasSignature.value = true
}

const handleTouchMove = (e) => {
    e.preventDefault()
    if (!isDrawing.value) return
    const pos = getTouchPos(e)
    ctx.value.lineTo(pos.x, pos.y)
    ctx.value.stroke()
    hasSignature.value = true
}

const stopDrawing = () => {
    isDrawing.value = false
}

const clearSignature = () => {
    const canvasEl = canvas.value
    ctx.value.fillStyle = '#ffffff'
    ctx.value.fillRect(0, 0, canvasEl.width / 2, canvasEl.height / 2)
    hasSignature.value = false
}

const submitSignature = async () => {
    if (!hasSignature.value) {
        error.value = 'Veuillez signer le document'
        return
    }

    if (!signerName.value.trim()) {
        error.value = 'Veuillez entrer votre nom complet'
        return
    }

    if (!acceptedTerms.value) {
        error.value = 'Veuillez accepter les conditions'
        return
    }

    error.value = ''
    isSubmitting.value = true

    try {
        const signatureData = canvas.value.toDataURL('image/png')

        const response = await fetch(route('signature.sign', props.signature.signature_token), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                signature_data: signatureData,
                signer_name: signerName.value,
                accepted_terms: acceptedTerms.value,
            }),
        })

        const data = await response.json()

        if (data.success) {
            window.location.href = data.redirect
        } else {
            error.value = data.error || 'Une erreur est survenue'
        }
    } catch (err) {
        error.value = 'Erreur de connexion. Veuillez réessayer.'
    } finally {
        isSubmitting.value = false
    }
}

const refuseSignature = async () => {
    try {
        const response = await fetch(route('signature.refuse', props.signature.signature_token), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                reason: refuseReason.value,
            }),
        })

        if (response.ok) {
            window.location.reload()
        }
    } catch (err) {
        error.value = 'Erreur lors du refus'
    }
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">{{ tenant?.name || 'Boxibox' }}</h1>
                            <p class="text-sm text-gray-500">Signature de contrat</p>
                        </div>
                    </div>
                    <div v-if="daysUntilExpiry !== null" class="text-right">
                        <span class="text-sm text-gray-500">Expire dans</span>
                        <p class="text-lg font-semibold" :class="daysUntilExpiry <= 3 ? 'text-red-600' : 'text-gray-900'">
                            {{ daysUntilExpiry }} jour{{ daysUntilExpiry > 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 py-8">
            <!-- Contract Summary -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <svg class="w-5 h-5 inline-block mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Contrat n{{ contract?.contract_number }}
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Customer Info -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Client</h3>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="font-medium text-gray-900">
                                {{ customer?.type === 'company' ? customer?.company_name : `${customer?.first_name} ${customer?.last_name}` }}
                            </p>
                            <p class="text-sm text-gray-600">{{ customer?.email }}</p>
                            <p class="text-sm text-gray-600">{{ customer?.phone }}</p>
                        </div>
                    </div>

                    <!-- Box Info -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Box loue</h3>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="font-medium text-gray-900">{{ contract?.box?.name }} - {{ contract?.box?.code }}</p>
                            <p class="text-sm text-gray-600">{{ contract?.box?.formatted_dimensions }}</p>
                            <p class="text-sm text-gray-600">Volume: {{ contract?.box?.formatted_volume }}</p>
                        </div>
                    </div>

                    <!-- Contract Details -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Details du contrat</h3>
                        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date de debut</span>
                                <span class="font-medium">{{ formatDate(contract?.start_date) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Duree</span>
                                <span class="font-medium">{{ contract?.type === 'monthly' ? 'Mensuel' : contract?.renewal_period + ' mois' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Facturation</span>
                                <span class="font-medium">{{ contract?.billing_frequency === 'monthly' ? 'Mensuelle' : 'Annuelle' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Price Info -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Tarif</h3>
                        <div class="bg-blue-50 rounded-xl p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Loyer mensuel</span>
                                <span class="font-bold text-lg text-blue-600">{{ formatCurrency(contract?.monthly_price) }}</span>
                            </div>
                            <div v-if="contract?.deposit_amount" class="flex justify-between">
                                <span class="text-gray-600">Depot de garantie</span>
                                <span class="font-medium">{{ formatCurrency(contract?.deposit_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Download Contract Button -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a :href="route('signature.download-contract', signature.signature_token)"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Telecharger le contrat complet (PDF)
                    </a>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <svg class="w-5 h-5 inline-block mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Votre signature
                </h2>

                <!-- Error message -->
                <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-600">{{ error }}</p>
                </div>

                <!-- Signer name input -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nom complet du signataire *
                    </label>
                    <input
                        v-model="signerName"
                        type="text"
                        placeholder="Entrez votre nom complet"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    />
                </div>

                <!-- Signature canvas -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Signez dans le cadre ci-dessous *
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-2 bg-gray-50">
                        <canvas
                            ref="canvas"
                            class="w-full h-48 bg-white rounded-lg cursor-crosshair touch-none"
                            style="touch-action: none;"
                        ></canvas>
                    </div>
                    <div class="flex justify-end mt-2">
                        <button
                            @click="clearSignature"
                            type="button"
                            class="text-sm text-gray-600 hover:text-gray-900 flex items-center"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Effacer la signature
                        </button>
                    </div>
                </div>

                <!-- Terms acceptance -->
                <div class="mb-6">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input
                            v-model="acceptedTerms"
                            type="checkbox"
                            class="mt-1 w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-600">
                            J'ai lu et j'accepte les termes et conditions du contrat de location.
                            Je confirme que les informations fournies sont exactes et que je suis autorise a signer ce document.
                        </span>
                    </label>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        @click="submitSignature"
                        :disabled="isSubmitting || !hasSignature || !acceptedTerms || !signerName"
                        class="flex-1 py-3 px-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-500/50 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isSubmitting ? 'Signature en cours...' : 'Signer le contrat' }}
                    </button>
                    <button
                        @click="showRefuseModal = true"
                        type="button"
                        class="py-3 px-6 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors"
                    >
                        Refuser de signer
                    </button>
                </div>
            </div>

            <!-- Legal notice -->
            <div class="bg-blue-50 rounded-xl p-4 text-sm text-blue-800">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-medium mb-1">Signature electronique conforme eIDAS</p>
                        <p class="text-blue-700">
                            Votre signature electronique a la meme valeur juridique qu'une signature manuscrite
                            conformement au reglement europeen eIDAS. Un certificat de preuve sera genere et conserve.
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Refuse Modal -->
        <div v-if="showRefuseModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showRefuseModal = false"></div>
                <div class="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Refuser de signer</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Etes-vous sur de vouloir refuser de signer ce contrat ? Cette action est irreversible.
                    </p>
                    <textarea
                        v-model="refuseReason"
                        placeholder="Raison du refus (optionnel)"
                        rows="3"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-4"
                    ></textarea>
                    <div class="flex space-x-3">
                        <button
                            @click="showRefuseModal = false"
                            class="flex-1 py-2 px-4 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            @click="refuseSignature"
                            class="flex-1 py-2 px-4 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors"
                        >
                            Confirmer le refus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
