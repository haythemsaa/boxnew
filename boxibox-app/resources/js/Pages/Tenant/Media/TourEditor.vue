<template>
    <TenantLayout :title="'Editeur de Visite 360'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <Link :href="route('tenant.media.index')" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-arrow-left-line text-xl"></i>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tour.name }}</h1>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ tour.site?.name }} &bull; {{ tour.panoramas?.length || 0 }} panoramas
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="previewTour"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-colors"
                    >
                        <i class="ri-eye-line mr-2"></i>
                        Apercu
                    </button>
                    <button
                        @click="showSettings = true"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-medium transition-colors"
                    >
                        <i class="ri-settings-3-line mr-2"></i>
                        Parametres
                    </button>
                    <button
                        @click="showAddPanorama = true"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium shadow-sm transition-colors"
                    >
                        <i class="ri-add-line mr-2"></i>
                        Ajouter panorama
                    </button>
                </div>
            </div>

            <!-- Tour Status -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Statut:</span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-xs font-medium',
                                tour.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                            ]"
                        >
                            {{ tour.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Visibilite:</span>
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-xs font-medium',
                                tour.is_public
                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                            ]"
                        >
                            {{ tour.is_public ? 'Public' : 'Prive' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Vues:</span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ tour.view_count }}</span>
                    </div>
                    <div v-if="tour.start_panorama_id" class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Point de depart:</span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ startPanorama?.name || 'Non defini' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Panoramas Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Panoramas</h2>

                <div v-if="tour.panoramas && tour.panoramas.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="(panorama, index) in tour.panoramas"
                        :key="panorama.id"
                        class="group relative bg-gray-50 dark:bg-gray-700/50 rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                        :class="{ 'ring-2 ring-primary-500': tour.start_panorama_id === panorama.id }"
                    >
                        <!-- Thumbnail -->
                        <div class="aspect-video bg-gradient-to-br from-green-400 to-teal-500 relative">
                            <img
                                v-if="panorama.thumbnail_url"
                                :src="panorama.thumbnail_url"
                                :alt="panorama.name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <i class="ri-panorama-line text-5xl text-white/80"></i>
                            </div>

                            <!-- Order Badge -->
                            <div class="absolute top-2 left-2 w-6 h-6 bg-black/60 rounded-full flex items-center justify-center">
                                <span class="text-xs text-white font-medium">{{ index + 1 }}</span>
                            </div>

                            <!-- Start Badge -->
                            <div v-if="tour.start_panorama_id === panorama.id" class="absolute top-2 right-2">
                                <span class="px-2 py-0.5 bg-primary-500 text-white text-xs rounded font-medium">
                                    <i class="ri-play-fill"></i> Depart
                                </span>
                            </div>

                            <!-- Hover Actions -->
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button
                                    @click="viewPanorama(panorama)"
                                    class="p-2 bg-white rounded-full hover:bg-gray-100"
                                    title="Voir"
                                >
                                    <i class="ri-eye-line text-gray-700"></i>
                                </button>
                                <button
                                    @click="editPanorama(panorama)"
                                    class="p-2 bg-white rounded-full hover:bg-gray-100"
                                    title="Editer"
                                >
                                    <i class="ri-edit-line text-gray-700"></i>
                                </button>
                                <button
                                    @click="setStartPanorama(panorama)"
                                    class="p-2 bg-white rounded-full hover:bg-primary-100"
                                    title="Definir comme depart"
                                >
                                    <i class="ri-play-circle-line text-primary-600"></i>
                                </button>
                                <button
                                    @click="deletePanorama(panorama)"
                                    class="p-2 bg-white rounded-full hover:bg-red-100"
                                    title="Supprimer"
                                >
                                    <i class="ri-delete-bin-line text-red-500"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-3">
                            <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ panorama.name }}</h3>
                            <p v-if="panorama.description" class="text-sm text-gray-500 dark:text-gray-400 truncate mt-1">
                                {{ panorama.description }}
                            </p>
                            <div class="flex items-center gap-2 mt-2">
                                <span v-if="panorama.hotspots?.length" class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="ri-focus-3-line mr-1"></i>
                                    {{ panorama.hotspots.length }} hotspot(s)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-12 text-center">
                    <i class="ri-panorama-line text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun panorama</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Ajoutez des images 360 pour creer votre visite virtuelle
                    </p>
                    <button
                        @click="showAddPanorama = true"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium"
                    >
                        <i class="ri-add-line mr-2"></i>
                        Ajouter un panorama
                    </button>
                </div>
            </div>

            <!-- Available 360 Media -->
            <div v-if="availableMedia.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Images 360 disponibles
                    <span class="text-sm font-normal text-gray-500">({{ availableMedia.length }})</span>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Ces images 360 sont disponibles dans votre galerie. Cliquez pour les ajouter a la visite.
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
                    <div
                        v-for="media in availableMedia"
                        :key="media.id"
                        @click="addFromGallery(media)"
                        class="cursor-pointer aspect-video bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden hover:ring-2 hover:ring-primary-500 transition-all"
                    >
                        <img
                            v-if="media.thumbnail_url"
                            :src="media.thumbnail_url"
                            :alt="media.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-green-400 to-teal-500">
                            <i class="ri-panorama-line text-2xl text-white/80"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Panorama Modal -->
        <Teleport to="body">
            <div v-if="showAddPanorama" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddPanorama = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ajouter un panorama</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom *</label>
                                <input
                                    v-model="panoramaForm.name"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="ex: Entree principale"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea
                                    v-model="panoramaForm.description"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Description du panorama..."
                                ></textarea>
                            </div>

                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave="isDragging = false"
                                @drop.prevent="handleDrop"
                                :class="[
                                    'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
                                    isDragging
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-primary-400'
                                ]"
                            >
                                <input
                                    type="file"
                                    ref="fileInput"
                                    accept="image/*"
                                    @change="handleFileSelect"
                                    class="hidden"
                                />

                                <div v-if="panoramaForm.preview">
                                    <img :src="panoramaForm.preview" class="max-h-32 mx-auto rounded-lg mb-2" />
                                    <button @click="clearFile" class="text-sm text-red-500 hover:text-red-600">
                                        <i class="ri-close-line mr-1"></i> Supprimer
                                    </button>
                                </div>
                                <div v-else>
                                    <i class="ri-panorama-line text-4xl text-gray-400 mb-2"></i>
                                    <p class="text-gray-600 dark:text-gray-400 mb-2">
                                        Glissez une image 360 ou
                                    </p>
                                    <button
                                        @click="$refs.fileInput.click()"
                                        class="text-primary-600 hover:text-primary-700 font-medium"
                                    >
                                        Parcourir
                                    </button>
                                    <p class="text-xs text-gray-500 mt-2">
                                        JPG, PNG (recommande: 4000x2000px minimum)
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                @click="showAddPanorama = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Annuler
                            </button>
                            <button
                                @click="addPanorama"
                                :disabled="uploading || !panoramaForm.name || !panoramaForm.file"
                                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium disabled:opacity-50"
                            >
                                <i v-if="uploading" class="ri-loader-4-line animate-spin mr-2"></i>
                                {{ uploading ? 'Telechargement...' : 'Ajouter' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Settings Modal -->
        <Teleport to="body">
            <div v-if="showSettings" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showSettings = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Parametres de la visite</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                <input
                                    v-model="settingsForm.name"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea
                                    v-model="settingsForm.description"
                                    rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                ></textarea>
                            </div>

                            <div class="flex items-center gap-6">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="settingsForm.is_active" class="rounded text-primary-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="settingsForm.is_public" class="rounded text-primary-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Publique</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="settingsForm.autoplay" class="rounded text-primary-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Autoplay</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                @click="showSettings = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Annuler
                            </button>
                            <button
                                @click="saveSettings"
                                :disabled="saving"
                                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium disabled:opacity-50"
                            >
                                <i v-if="saving" class="ri-loader-4-line animate-spin mr-2"></i>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Panorama Preview Modal -->
        <Teleport to="body">
            <div v-if="previewPanorama" class="fixed inset-0 z-50 bg-black/90">
                <button @click="previewPanorama = null" class="absolute top-4 right-4 z-10 text-white text-2xl hover:text-gray-300">
                    <i class="ri-close-line"></i>
                </button>
                <div class="w-full h-full flex items-center justify-center">
                    <img :src="previewPanorama.image_url" class="max-w-full max-h-full object-contain" />
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    tour: Object,
    availableMedia: Array,
});

// State
const showAddPanorama = ref(false);
const showSettings = ref(false);
const previewPanorama = ref(null);
const isDragging = ref(false);
const uploading = ref(false);
const saving = ref(false);

// Forms
const panoramaForm = ref({
    name: '',
    description: '',
    file: null,
    preview: null,
});

const settingsForm = ref({
    name: props.tour.name,
    description: props.tour.description || '',
    is_active: props.tour.is_active,
    is_public: props.tour.is_public,
    autoplay: props.tour.autoplay,
});

// Computed
const startPanorama = computed(() => {
    return props.tour.panoramas?.find(p => p.id === props.tour.start_panorama_id);
});

// Methods
const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        panoramaForm.value.file = file;
        panoramaForm.value.preview = URL.createObjectURL(file);
    }
};

const handleDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        panoramaForm.value.file = file;
        panoramaForm.value.preview = URL.createObjectURL(file);
    }
};

const clearFile = () => {
    panoramaForm.value.file = null;
    panoramaForm.value.preview = null;
};

const addPanorama = async () => {
    uploading.value = true;

    const formData = new FormData();
    formData.append('name', panoramaForm.value.name);
    formData.append('description', panoramaForm.value.description);
    formData.append('file', panoramaForm.value.file);

    try {
        await axios.post(route('tenant.media.panoramas.store', props.tour.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        showAddPanorama.value = false;
        panoramaForm.value = { name: '', description: '', file: null, preview: null };
        router.reload();
    } catch (error) {
        console.error('Upload error:', error);
        alert('Erreur lors du telechargement');
    } finally {
        uploading.value = false;
    }
};

const addFromGallery = async (media) => {
    const name = prompt('Nom du panorama:', media.title || 'Panorama');
    if (!name) return;

    try {
        await axios.post(route('tenant.media.panoramas.store', props.tour.id), {
            site_media_id: media.id,
            name: name,
        });
        router.reload();
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors de l\'ajout');
    }
};

const viewPanorama = (panorama) => {
    previewPanorama.value = panorama;
};

const editPanorama = (panorama) => {
    // TODO: Open edit modal for hotspots
    console.log('Edit panorama:', panorama);
};

const setStartPanorama = async (panorama) => {
    try {
        await axios.put(route('tenant.media.tours.update', props.tour.id), {
            start_panorama_id: panorama.id
        });
        router.reload();
    } catch (error) {
        console.error('Error:', error);
    }
};

const deletePanorama = async (panorama) => {
    if (!confirm('Supprimer ce panorama ?')) return;

    try {
        await axios.delete(route('tenant.media.panoramas.destroy', panorama.id));
        router.reload();
    } catch (error) {
        console.error('Error:', error);
    }
};

const saveSettings = async () => {
    saving.value = true;

    try {
        await axios.put(route('tenant.media.tours.update', props.tour.id), settingsForm.value);
        showSettings.value = false;
        router.reload();
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors de l\'enregistrement');
    } finally {
        saving.value = false;
    }
};

const previewTour = () => {
    // Open tour preview in new tab
    window.open(`/tour/${props.tour.id}/preview`, '_blank');
};
</script>
