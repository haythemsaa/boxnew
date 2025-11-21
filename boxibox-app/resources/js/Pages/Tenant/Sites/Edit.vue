<template>
    <AuthenticatedLayout title="Edit Site">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    :href="route('tenant.sites.index')"
                    class="text-sm text-gray-600 hover:text-gray-900 mb-4 inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Sites
                </Link>
                <h2 class="text-2xl font-bold text-gray-900">Edit Site</h2>
                <p class="mt-1 text-sm text-gray-600">Update site information</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <form @submit.prevent="submit">
                    <!-- Site Information -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Site Information</h3>

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Site Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.name }"
                                placeholder="e.g., Paris Centre"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Code -->
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                                Site Code <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="code"
                                v-model="form.code"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.code }"
                                placeholder="e.g., PAR-001"
                            />
                            <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">
                                {{ form.errors.code }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">Unique identifier for this site</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.description }"
                                placeholder="Brief description of this site"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <!-- Status -->
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
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Address Information</h3>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                                Street Address <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="address"
                                v-model="form.address"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.address }"
                                placeholder="e.g., 123 Rue de Rivoli"
                            />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                {{ form.errors.address }}
                            </p>
                        </div>

                        <!-- City & Postal Code -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                                    City <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.city }"
                                    placeholder="e.g., Paris"
                                />
                                <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.city }}
                                </p>
                            </div>

                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Postal Code <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.postal_code }"
                                    placeholder="e.g., 75001"
                                />
                                <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.postal_code }}
                                </p>
                            </div>
                        </div>

                        <!-- Country -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
                                Country <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="country"
                                v-model="form.country"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.country }"
                                placeholder="e.g., France"
                            />
                            <p v-if="form.errors.country" class="mt-1 text-sm text-red-600">
                                {{ form.errors.country }}
                            </p>
                        </div>

                        <!-- Coordinates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">
                                    Latitude
                                </label>
                                <input
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="number"
                                    step="any"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="e.g., 48.8566"
                                />
                            </div>

                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">
                                    Longitude
                                </label>
                                <input
                                    id="longitude"
                                    v-model="form.longitude"
                                    type="number"
                                    step="any"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="e.g., 2.3522"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Contact Information</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="e.g., +33 1 23 45 67 89"
                                />
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="e.g., paris@example.com"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex items-center justify-end space-x-3 pt-6 border-t">
                        <Link
                            :href="route('tenant.sites.index')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!form.processing">Update Site</span>
                            <span v-else class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    site: Object,
})

const form = useForm({
    name: props.site.name,
    code: props.site.code,
    description: props.site.description,
    status: props.site.status,
    address: props.site.address,
    city: props.site.city,
    postal_code: props.site.postal_code,
    country: props.site.country,
    latitude: props.site.latitude,
    longitude: props.site.longitude,
    phone: props.site.phone,
    email: props.site.email,
})

const submit = () => {
    form.put(route('tenant.sites.update', props.site.id))
}
</script>
