<template>
    <div class="w-full">
        <!-- Canvas -->
        <div class="border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-white">
            <canvas
                ref="canvas"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseout="stopDrawing"
                @touchstart="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="stopDrawing"
                class="w-full bg-white cursor-crosshair"
                :style="{ aspectRatio: '4/2' }"
            ></canvas>
        </div>

        <!-- Controls -->
        <div class="mt-4 flex items-center gap-3">
            <button
                @click="clearSignature"
                class="px-4 py-2 bg-red-100 text-red-700 hover:bg-red-200 rounded-lg transition-colors font-medium text-sm"
            >
                Effacer
            </button>
            <button
                @click="undoSignature"
                :disabled="!canUndo"
                class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors font-medium text-sm disabled:opacity-50"
            >
                Annuler
            </button>
            <button
                @click="redoSignature"
                :disabled="!canRedo"
                class="px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors font-medium text-sm disabled:opacity-50"
            >
                Refaire
            </button>
            <div class="flex-1"></div>
            <span
                v-if="!isEmpty"
                class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-sm font-medium flex items-center gap-1"
            >
                ✓ Capturee
            </span>
        </div>

        <!-- Info -->
        <p class="mt-2 text-xs text-gray-500">Signez avec votre doigt ou souris (auto-sauvegarde)</p>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({
    label: String,
    required: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['signature-saved'])

const canvas = ref(null)
const ctx = ref(null)
const isDrawing = ref(false)
const isEmpty = ref(true)
const history = ref([])
const historyStep = ref(0)

const canUndo = computed(() => historyStep.value > 0)
const canRedo = computed(() => historyStep.value < history.value.length - 1)

onMounted(() => {
    const c = canvas.value
    c.width = c.offsetWidth
    c.height = c.offsetHeight * 2

    ctx.value = c.getContext('2d')
    ctx.value.lineCap = 'round'
    ctx.value.lineJoin = 'round'
    ctx.value.lineWidth = 2
    ctx.value.strokeStyle = '#000'

    // Save initial state
    saveState()
})

const saveState = () => {
    const canvasImage = canvas.value.toDataURL()
    history.value = history.value.slice(0, historyStep.value + 1)
    history.value.push(canvasImage)
    historyStep.value = history.value.length - 1
}

const startDrawing = (e) => {
    const rect = canvas.value.getBoundingClientRect()
    const x = (e.clientX - rect.left) * (canvas.value.width / rect.width)
    const y = (e.clientY - rect.top) * (canvas.value.height / rect.height)

    isDrawing.value = true
    ctx.value.beginPath()
    ctx.value.moveTo(x, y)
}

const draw = (e) => {
    if (!isDrawing.value) return

    const rect = canvas.value.getBoundingClientRect()
    const x = (e.clientX - rect.left) * (canvas.value.width / rect.width)
    const y = (e.clientY - rect.top) * (canvas.value.height / rect.height)

    ctx.value.lineTo(x, y)
    ctx.value.stroke()
    isEmpty.value = false
}

const stopDrawing = () => {
    if (isDrawing.value) {
        isDrawing.value = false
        ctx.value.closePath()
        saveState()
        // Auto-emit signature after each stroke
        if (!isEmpty.value) {
            const signatureData = canvas.value.toDataURL('image/png')
            emit('signature-saved', signatureData)
        }
    }
}

const handleTouchStart = (e) => {
    const touch = e.touches[0]
    const rect = canvas.value.getBoundingClientRect()
    const x = (touch.clientX - rect.left) * (canvas.value.width / rect.width)
    const y = (touch.clientY - rect.top) * (canvas.value.height / rect.height)

    isDrawing.value = true
    ctx.value.beginPath()
    ctx.value.moveTo(x, y)
}

const handleTouchMove = (e) => {
    if (!isDrawing.value) return
    e.preventDefault()

    const touch = e.touches[0]
    const rect = canvas.value.getBoundingClientRect()
    const x = (touch.clientX - rect.left) * (canvas.value.width / rect.width)
    const y = (touch.clientY - rect.top) * (canvas.value.height / rect.height)

    ctx.value.lineTo(x, y)
    ctx.value.stroke()
    isEmpty.value = false
}

const clearSignature = () => {
    ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height)
    isEmpty.value = true
    historyStep.value = 0
    history.value = ['']
    saveState()
    emit('signature-saved', null)
}

const undoSignature = () => {
    if (canUndo.value) {
        historyStep.value--
        restoreState()
        // Check if canvas is empty after undo
        setTimeout(() => {
            const imageData = ctx.value.getImageData(0, 0, canvas.value.width, canvas.value.height)
            const hasContent = imageData.data.some((channel, index) => index % 4 === 3 && channel !== 0)
            isEmpty.value = !hasContent
            if (hasContent) {
                emit('signature-saved', canvas.value.toDataURL('image/png'))
            } else {
                emit('signature-saved', null)
            }
        }, 100)
    }
}

const redoSignature = () => {
    if (canRedo.value) {
        historyStep.value++
        restoreState()
        setTimeout(() => {
            emit('signature-saved', canvas.value.toDataURL('image/png'))
            isEmpty.value = false
        }, 100)
    }
}

const restoreState = () => {
    const canvasImage = history.value[historyStep.value]
    if (canvasImage) {
        const img = new Image()
        img.src = canvasImage
        img.onload = () => {
            ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height)
            ctx.value.drawImage(img, 0, 0)
        }
    } else {
        ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height)
    }
}

// Signature is auto-saved when user stops drawing - see stopDrawing()
</script>

<style scoped>
canvas {
    display: block;
    touch-action: none;
}
</style>
