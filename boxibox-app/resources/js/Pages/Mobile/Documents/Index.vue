<template>
    <MobileLayout title="Mes Documents" :show-back="true">
        <!-- Document Categories -->
        <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
            <button
                v-for="cat in categories"
                :key="cat.id"
                @click="activeCategory = cat.id"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    activeCategory === cat.id ? 'bg-primary-600 text-white' : 'bg-white text-gray-700'
                ]"
            >
                {{ cat.name }}
            </button>
        </div>

        <!-- Documents List -->
        <div class="space-y-3">
            <div
                v-for="doc in filteredDocuments"
                :key="doc.id"
                class="bg-white rounded-xl shadow-sm overflow-hidden"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-4" :class="getDocTypeClass(doc.type)">
                            <component :is="getDocIcon(doc.type)" class="w-6 h-6" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 truncate">{{ doc.name }}</h4>
                            <p class="text-sm text-gray-500">{{ doc.description }}</p>
                            <div class="flex items-center mt-1 text-xs text-gray-400">
                                <span>{{ formatDate(doc.created_at) }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ formatFileSize(doc.size) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex space-x-2 mt-4">
                        <button
                            @click="viewDocument(doc)"
                            class="flex-1 flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium"
                        >
                            <EyeIcon class="w-4 h-4 mr-2" />
                            Voir
                        </button>
                        <button
                            @click="downloadDocument(doc)"
                            class="flex-1 flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded-lg font-medium"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                            Telecharger
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredDocuments.length === 0" class="text-center py-12">
            <DocumentIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun document</h3>
            <p class="text-gray-500">Vous n'avez pas encore de documents dans cette categorie.</p>
        </div>

        <!-- Upload Section -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajouter un document</h3>
            <p class="text-sm text-gray-500 mb-4">
                Vous pouvez nous envoyer des documents complementaires (justificatifs, attestations, etc.)
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6">
                <input
                    type="file"
                    ref="fileInput"
                    @change="handleFileSelect"
                    class="hidden"
                    accept=".pdf,.jpg,.jpeg,.png"
                />
                <button
                    @click="$refs.fileInput.click()"
                    class="w-full flex flex-col items-center text-gray-500"
                >
                    <CloudArrowUpIcon class="w-10 h-10 mb-2" />
                    <span class="font-medium">Cliquer pour selectionner</span>
                    <span class="text-sm mt-1">PDF, JPG ou PNG (max 10 Mo)</span>
                </button>
            </div>

            <div v-if="selectedFile" class="mt-4 p-4 bg-gray-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <DocumentIcon class="w-8 h-8 text-gray-400 mr-3" />
                        <div>
                            <p class="font-medium text-gray-900">{{ selectedFile.name }}</p>
                            <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                        </div>
                    </div>
                    <button @click="selectedFile = null" class="text-red-500">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de document</label>
                    <select
                        v-model="uploadType"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white"
                    >
                        <option value="identity">Piece d'identite</option>
                        <option value="proof_address">Justificatif de domicile</option>
                        <option value="insurance">Attestation d'assurance</option>
                        <option value="other">Autre</option>
                    </select>
                </div>

                <button
                    @click="uploadDocument"
                    :disabled="uploading"
                    class="w-full mt-4 py-3 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                >
                    {{ uploading ? 'Envoi en cours...' : 'Envoyer le document' }}
                </button>
            </div>
        </div>

        <!-- Document Viewer Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="viewingDocument" class="fixed inset-0 z-50 bg-black">
                <div class="absolute top-0 left-0 right-0 flex items-center justify-between p-4 bg-gradient-to-b from-black/50 to-transparent">
                    <h3 class="text-white font-semibold truncate">{{ viewingDocument.name }}</h3>
                    <button @click="viewingDocument = null" class="text-white">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>
                <div class="h-full flex items-center justify-center p-4">
                    <iframe
                        v-if="viewingDocument.mime_type === 'application/pdf'"
                        :src="viewingDocument.url"
                        class="w-full h-full rounded-lg"
                    />
                    <img
                        v-else
                        :src="viewingDocument.url"
                        :alt="viewingDocument.name"
                        class="max-w-full max-h-full object-contain"
                    />
                </div>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    DocumentIcon,
    DocumentTextIcon,
    DocumentCheckIcon,
    IdentificationIcon,
    ShieldCheckIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    CloudArrowUpIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    documents: Array,
})

const activeCategory = ref('all')
const selectedFile = ref(null)
const uploadType = ref('other')
const uploading = ref(false)
const viewingDocument = ref(null)

const categories = [
    { id: 'all', name: 'Tous' },
    { id: 'contract', name: 'Contrats' },
    { id: 'invoice', name: 'Factures' },
    { id: 'identity', name: 'Identite' },
    { id: 'insurance', name: 'Assurance' },
    { id: 'other', name: 'Autres' },
]

const filteredDocuments = computed(() => {
    if (!props.documents) return []
    if (activeCategory.value === 'all') return props.documents
    return props.documents.filter(d => d.type === activeCategory.value)
})

const getDocTypeClass = (type) => {
    const classes = {
        contract: 'bg-blue-100 text-blue-600',
        invoice: 'bg-green-100 text-green-600',
        identity: 'bg-purple-100 text-purple-600',
        insurance: 'bg-yellow-100 text-yellow-600',
        other: 'bg-gray-100 text-gray-600',
    }
    return classes[type] || classes.other
}

const getDocIcon = (type) => {
    const icons = {
        contract: DocumentCheckIcon,
        invoice: DocumentTextIcon,
        identity: IdentificationIcon,
        insurance: ShieldCheckIcon,
        other: DocumentIcon,
    }
    return icons[type] || DocumentIcon
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const formatFileSize = (bytes) => {
    if (!bytes) return '-'
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return Math.round(bytes / 1024) + ' Ko'
    return (bytes / 1048576).toFixed(1) + ' Mo'
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert('Le fichier est trop volumineux (max 10 Mo)')
            return
        }
        selectedFile.value = file
    }
}

const uploadDocument = () => {
    if (!selectedFile.value) return

    uploading.value = true
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('type', uploadType.value)

    router.post(route('mobile.documents.upload'), formData, {
        onFinish: () => {
            uploading.value = false
            selectedFile.value = null
            uploadType.value = 'other'
        },
    })
}

const viewDocument = (doc) => {
    viewingDocument.value = doc
}

const downloadDocument = (doc) => {
    window.location.href = route('mobile.documents.download', doc.id)
}
</script>
