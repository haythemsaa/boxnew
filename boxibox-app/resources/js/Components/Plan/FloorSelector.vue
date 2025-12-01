<template>
    <div class="flex items-center gap-3 p-4 bg-white rounded-lg shadow-md border border-gray-200">
        <label class="text-sm font-semibold text-gray-700">Étage :</label>

        <select
            v-model="selectedFloor"
            @change="$emit('change', selectedFloor)"
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm"
        >
            <option :value="null">Tous les étages</option>
            <option v-for="floor in floors" :key="floor.id" :value="floor.id">
                {{ floor.name }} ({{ floor.floor_number > 0 ? 'Étage ' + floor.floor_number : 'RDC' }})
            </option>
            <option value="new">+ Ajouter un étage</option>
        </select>

        <!-- Quick add floor modal trigger -->
        <button
            v-if="selectedFloor === 'new'"
            @click="showAddFloorModal = true"
            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium"
        >
            Créer un étage
        </button>

        <!-- Floor info -->
        <div v-if="currentFloor" class="flex items-center gap-2 text-xs text-gray-600">
            <span class="px-2 py-1 bg-blue-50 rounded">{{ currentFloor.total_area || '0' }} m²</span>
        </div>
    </div>

    <!-- Add Floor Modal -->
    <transition name="modal">
        <div v-if="showAddFloorModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    Ajouter un nouvel étage
                </h2>

                <form @submit.prevent="addFloor" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nom de l'étage
                        </label>
                        <input
                            v-model="newFloor.name"
                            type="text"
                            placeholder="ex: Étage 1, Sous-sol, Entresol"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Numéro d'étage
                        </label>
                        <input
                            v-model.number="newFloor.floor_number"
                            type="number"
                            placeholder="0 pour RDC, -1 pour sous-sol, 1 pour 1er étage"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Surface (m²)
                        </label>
                        <input
                            v-model.number="newFloor.total_area"
                            type="number"
                            step="0.01"
                            placeholder="Surface totale en m²"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea
                            v-model="newFloor.description"
                            placeholder="Description optionnelle..."
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            @click="closeAddFloorModal"
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium disabled:opacity-50"
                        >
                            {{ isSubmitting ? 'Création...' : 'Créer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    floors: {
        type: Array,
        default: () => [],
    },
    siteId: Number,
})

const emit = defineEmits(['change', 'floor-added'])

const selectedFloor = ref(null)
const showAddFloorModal = ref(false)
const isSubmitting = ref(false)

const newFloor = ref({
    name: '',
    floor_number: 0,
    total_area: null,
    description: '',
})

const currentFloor = computed(() => {
    if (!selectedFloor.value) return null
    return props.floors.find(f => f.id === selectedFloor.value)
})

const closeAddFloorModal = () => {
    showAddFloorModal.value = false
    selectedFloor.value = null
    resetFloorForm()
}

const resetFloorForm = () => {
    newFloor.value = {
        name: '',
        floor_number: 0,
        total_area: null,
        description: '',
    }
}

const addFloor = async () => {
    isSubmitting.value = true

    const form = useForm({
        site_id: props.siteId,
        name: newFloor.value.name,
        floor_number: newFloor.value.floor_number,
        total_area: newFloor.value.total_area,
        description: newFloor.value.description,
    })

    try {
        // This would typically post to a create floor endpoint
        // For now, we'll just emit the data
        emit('floor-added', newFloor.value)

        closeAddFloorModal()
    } catch (error) {
        console.error('Error adding floor:', error)
    } finally {
        isSubmitting.value = false
    }
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: scale(0.95);
}
</style>
