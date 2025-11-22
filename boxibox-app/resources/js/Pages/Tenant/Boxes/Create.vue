<template>
    <AuthenticatedLayout title="Create Box">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    :href="route('tenant.boxes.index')"
                    class="text-sm text-gray-600 hover:text-gray-900 mb-4 inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Boxes
                </Link>
                <h2 class="text-2xl font-bold text-gray-900">Create New Box</h2>
                <p class="mt-1 text-sm text-gray-600">Add a new storage box to your inventory</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <form @submit.prevent="submit">
                    <!-- Basic Information -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Basic Information</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Box Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="e.g., Box A-101"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Box Code <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.code }"
                                    placeholder="e.g., BOX-A101"
                                />
                                <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.code }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="site_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Site <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="site_id"
                                v-model="form.site_id"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.site_id }"
                            >
                                <option value="">Select a site</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }} ({{ site.code }})
                                </option>
                            </select>
                            <p v-if="form.errors.site_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.site_id }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="building_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Building (Optional)
                                </label>
                                <select
                                    id="building_id"
                                    v-model="form.building_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                >
                                    <option value="">No building</option>
                                    <option v-for="building in filteredBuildings" :key="building.id" :value="building.id">
                                        {{ building.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="floor_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Floor (Optional)
                                </label>
                                <select
                                    id="floor_id"
                                    v-model="form.floor_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                >
                                    <option value="">No floor</option>
                                    <option v-for="floor in filteredFloors" :key="floor.id" :value="floor.id">
                                        {{ floor.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Brief description of this box"
                            ></textarea>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            >
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="reserved">Reserved</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dimensions -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Dimensions</h3>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="length" class="block text-sm font-medium text-gray-700 mb-1">
                                    Length (m) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="length"
                                    v-model="form.length"
                                    type="number"
                                    step="0.1"
                                    min="0.1"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.length }"
                                    placeholder="e.g., 2.5"
                                />
                                <p v-if="form.errors.length" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.length }}
                                </p>
                            </div>

                            <div>
                                <label for="width" class="block text-sm font-medium text-gray-700 mb-1">
                                    Width (m) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="width"
                                    v-model="form.width"
                                    type="number"
                                    step="0.1"
                                    min="0.1"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.width }"
                                    placeholder="e.g., 2.0"
                                />
                                <p v-if="form.errors.width" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.width }}
                                </p>
                            </div>

                            <div>
                                <label for="height" class="block text-sm font-medium text-gray-700 mb-1">
                                    Height (m) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="height"
                                    v-model="form.height"
                                    type="number"
                                    step="0.1"
                                    min="0.1"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.height }"
                                    placeholder="e.g., 2.5"
                                />
                                <p v-if="form.errors.height" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.height }}
                                </p>
                            </div>
                        </div>

                        <div v-if="calculatedVolume" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm text-blue-800">
                                <strong>Calculated Volume:</strong> {{ calculatedVolume }} m³
                            </p>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Pricing</h3>

                        <div>
                            <label for="base_price" class="block text-sm font-medium text-gray-700 mb-1">
                                Base Price ($/month) <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="base_price"
                                v-model="form.base_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.base_price }"
                                placeholder="e.g., 150.00"
                            />
                            <p v-if="form.errors.base_price" class="mt-1 text-sm text-red-600">
                                {{ form.errors.base_price }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">Current price will be set to base price initially</p>
                        </div>
                    </div>

                    <!-- Features & Amenities -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Features & Amenities</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3">
                                <input
                                    v-model="form.climate_controlled"
                                    type="checkbox"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700">Climate Controlled</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input
                                    v-model="form.has_electricity"
                                    type="checkbox"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700">Has Electricity</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input
                                    v-model="form.has_alarm"
                                    type="checkbox"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700">Has Alarm</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input
                                    v-model="form.has_24_7_access"
                                    type="checkbox"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700">24/7 Access</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input
                                    v-model="form.has_wifi"
                                    type="checkbox"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700">Has WiFi</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input
                                    v-model="form.has_shelving"
                                    type="checkbox"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700">Has Shelving</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input
                                    v-model="form.is_ground_floor"
                                    type="checkbox"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-700">Ground Floor</span>
                            </label>
                        </div>
                    </div>

                    <!-- Additional Details -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Additional Details</h3>

                        <div>
                            <label for="access_code" class="block text-sm font-medium text-gray-700 mb-1">
                                Access Code
                            </label>
                            <input
                                id="access_code"
                                v-model="form.access_code"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="e.g., 1234"
                            />
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Any additional notes or special instructions"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex items-center justify-end space-x-3 pt-6 border-t">
                        <Link
                            :href="route('tenant.boxes.index')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!form.processing">Create Box</span>
                            <span v-else class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    sites: Array,
    buildings: Array,
    floors: Array,
})

const form = useForm({
    site_id: '',
    building_id: '',
    floor_id: '',
    name: '',
    code: '',
    description: '',
    length: null,
    width: null,
    height: null,
    base_price: null,
    status: 'available',
    climate_controlled: false,
    has_electricity: false,
    has_alarm: false,
    has_24_7_access: false,
    has_wifi: false,
    has_shelving: false,
    is_ground_floor: false,
    access_code: '',
    notes: '',
})

const calculatedVolume = computed(() => {
    if (form.length && form.width && form.height) {
        return (form.length * form.width * form.height).toFixed(2)
    }
    return null
})

const filteredBuildings = computed(() => {
    if (!form.site_id) return []
    return props.buildings.filter(b => b.site_id == form.site_id)
})

const filteredFloors = computed(() => {
    if (!form.building_id) return []
    return props.floors.filter(f => f.building_id == form.building_id)
})

const submit = () => {
    form.post(route('tenant.boxes.store'))
}
</script>
