<template>
    <div class="space-y-6">
        <!-- Export Section -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    Exporter le plan
                </h3>
                <ArrowDownTrayIcon class="h-5 w-5 text-gray-400" />
            </div>

            <p class="text-sm text-gray-600 mb-4">
                Téléchargez votre plan en plusieurs formats
            </p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <button
                    @click="exportAs('png')"
                    :disabled="isExporting"
                    class="px-4 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg transition-colors font-medium disabled:opacity-50 text-sm"
                >
                    {{ isExporting && exportFormat === 'png' ? 'Export...' : 'PNG' }}
                </button>

                <button
                    @click="exportAs('pdf')"
                    :disabled="isExporting"
                    class="px-4 py-2 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg transition-colors font-medium disabled:opacity-50 text-sm"
                >
                    {{ isExporting && exportFormat === 'pdf' ? 'Export...' : 'PDF' }}
                </button>

                <button
                    @click="exportAs('svg')"
                    :disabled="isExporting"
                    class="px-4 py-2 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded-lg transition-colors font-medium disabled:opacity-50 text-sm"
                >
                    {{ isExporting && exportFormat === 'svg' ? 'Export...' : 'SVG' }}
                </button>

                <button
                    @click="exportAs('json')"
                    :disabled="isExporting"
                    class="px-4 py-2 bg-green-50 text-green-700 hover:bg-green-100 rounded-lg transition-colors font-medium disabled:opacity-50 text-sm"
                >
                    {{ isExporting && exportFormat === 'json' ? 'Export...' : 'JSON' }}
                </button>
            </div>

            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                <p><strong>PNG/PDF:</strong> Image bitmap pour impression et partage</p>
                <p><strong>SVG:</strong> Format vectoriel éditable</p>
                <p><strong>JSON:</strong> Données brutes pour sauvegarde/restauration</p>
            </div>
        </div>

        <!-- Import Section -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    Importer un plan
                </h3>
                <ArrowUpTrayIcon class="h-5 w-5 text-gray-400" />
            </div>

            <p class="text-sm text-gray-600 mb-4">
                Restaurez un plan précédemment exporté (format JSON)
            </p>

            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleFileDrop"
                :class="[
                    'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                    isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 bg-gray-50'
                ]"
            >
                <input
                    ref="fileInput"
                    type="file"
                    accept=".json"
                    @change="handleFileSelect"
                    class="hidden"
                />

                <div class="space-y-3">
                    <ArrowUpTrayIcon class="h-8 w-8 text-gray-400 mx-auto" />
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            Glissez un fichier JSON ici
                        </p>
                        <p class="text-xs text-gray-600 mt-1">
                            ou
                            <button
                                @click="$refs.fileInput?.click()"
                                class="text-primary-600 hover:text-primary-700 font-medium"
                            >
                                parcourez votre ordinateur
                            </button>
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="selectedFile" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-900 font-medium">
                    Fichier sélectionné : {{ selectedFile.name }}
                </p>
                <div class="flex gap-2 mt-3">
                    <button
                        @click="importPlan"
                        :disabled="isImporting"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50"
                    >
                        {{ isImporting ? 'Import...' : 'Importer' }}
                    </button>
                    <button
                        @click="selectedFile = null"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm font-medium"
                    >
                        Annuler
                    </button>
                </div>
            </div>

            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                <p>⚠️ L'importation remplacera tous les éléments du plan actuel</p>
            </div>
        </div>

        <!-- Backup History -->
        <div v-if="backups.length" class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Sauvegardes automatiques
            </h3>

            <div class="space-y-2">
                <div
                    v-for="backup in backups"
                    :key="backup.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            {{ backup.name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ formatDate(backup.created_at) }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="restoreBackup(backup)"
                            class="px-3 py-1 text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 rounded transition-colors font-medium"
                        >
                            Restaurer
                        </button>
                        <button
                            @click="downloadBackup(backup)"
                            class="px-3 py-1 text-xs bg-gray-200 text-gray-700 hover:bg-gray-300 rounded transition-colors"
                        >
                            <ArrowDownTrayIcon class="h-3 w-3" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Toast -->
        <transition name="slide-down">
            <div v-if="successMessage" class="fixed top-4 right-4 px-4 py-3 bg-green-500 text-white rounded-lg shadow-lg">
                {{ successMessage }}
            </div>
        </transition>

        <!-- Error Toast -->
        <transition name="slide-down">
            <div v-if="errorMessage" class="fixed top-4 right-4 px-4 py-3 bg-red-500 text-white rounded-lg shadow-lg">
                {{ errorMessage }}
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import {
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    elements: Array,
    configuration: Object,
    siteId: Number,
    backups: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['import', 'export'])

const fileInput = ref(null)
const selectedFile = ref(null)
const isDragging = ref(false)
const isExporting = ref(false)
const isImporting = ref(false)
const exportFormat = ref(null)
const successMessage = ref('')
const errorMessage = ref('')

const handleFileSelect = (event) => {
    selectedFile.value = event.target.files[0]
}

const handleFileDrop = (event) => {
    isDragging.value = false
    const files = event.dataTransfer.files
    if (files.length) {
        selectedFile.value = files[0]
    }
}

const exportAs = async (format) => {
    isExporting.value = true
    exportFormat.value = format

    try {
        const data = {
            elements: props.elements,
            configuration: props.configuration,
            exported_at: new Date().toISOString(),
            format: format,
        }

        if (format === 'json') {
            downloadJSON(data)
        } else if (format === 'svg') {
            downloadSVG(data)
        } else if (format === 'png' || format === 'pdf') {
            // These would require canvas/PDF library
            downloadImage(format, data)
        }

        successMessage.value = `Plan exporté en ${format.toUpperCase()}`
        setTimeout(() => successMessage.value = '', 3000)
    } catch (error) {
        errorMessage.value = `Erreur lors de l'export : ${error.message}`
        setTimeout(() => errorMessage.value = '', 3000)
    } finally {
        isExporting.value = false
    }
}

const downloadJSON = (data) => {
    const json = JSON.stringify(data, null, 2)
    const blob = new Blob([json], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `plan-${new Date().getTime()}.json`
    a.click()
    URL.revokeObjectURL(url)
}

const downloadSVG = (data) => {
    // Simple SVG export - more complex in actual implementation
    let svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1920" height="1080">'

    data.elements?.forEach(element => {
        if (element.element_type === 'box') {
            svg += `
                <rect x="${element.x}" y="${element.y}"
                      width="${element.width}" height="${element.height}"
                      fill="${element.fill_color || '#f0f0f0'}"
                      stroke="${element.stroke_color || '#333'}"
                      stroke-width="${element.stroke_width || 1}"/>
                <text x="${element.x + 5}" y="${element.y + 15}" font-size="12">
                    ${element.label || ''}
                </text>
            `
        }
    })

    svg += '</svg>'

    const blob = new Blob([svg], { type: 'image/svg+xml' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `plan-${new Date().getTime()}.svg`
    a.click()
    URL.revokeObjectURL(url)
}

const downloadImage = (format, data) => {
    // Placeholder for PNG/PDF export
    console.log(`${format} export would require html2canvas or similar library`)
    successMessage.value = `Export ${format.toUpperCase()} disponible (implémentation avancée)`
    setTimeout(() => successMessage.value = '', 3000)
}

const importPlan = async () => {
    if (!selectedFile.value) return

    isImporting.value = true

    try {
        const text = await selectedFile.value.text()
        const data = JSON.parse(text)

        emit('import', data)

        successMessage.value = 'Plan importé avec succès'
        setTimeout(() => successMessage.value = '', 3000)

        selectedFile.value = null
        fileInput.value.value = ''
    } catch (error) {
        errorMessage.value = `Erreur lors de l'import : ${error.message}`
        setTimeout(() => errorMessage.value = '', 3000)
    } finally {
        isImporting.value = false
    }
}

const restoreBackup = (backup) => {
    // Restore backup logic
    emit('import', backup)
    successMessage.value = 'Sauvegarde restaurée'
    setTimeout(() => successMessage.value = '', 3000)
}

const downloadBackup = (backup) => {
    const json = JSON.stringify(backup, null, 2)
    const blob = new Blob([json], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${backup.name}-${backup.id}.json`
    a.click()
    URL.revokeObjectURL(url)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}
</style>
