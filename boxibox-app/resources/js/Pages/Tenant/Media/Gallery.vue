<template>
    <TenantLayout :title="'Galerie Media'">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Galerie Media</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Photos, videos et visites virtuelles 360 de vos sites
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="showUploadModal = true"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium shadow-sm transition-colors"
                    >
                        <i class="ri-upload-cloud-2-line mr-2"></i>
                        Telecharger
                    </button>
                    <button
                        @click="showTourModal = true"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium shadow-sm transition-colors"
                    >
                        <i class="ri-panorama-line mr-2"></i>
                        Nouvelle visite 360
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <i class="ri-image-line text-xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_photos }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Photos</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <i class="ri-video-line text-xl text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_videos }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Videos</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <i class="ri-panorama-line text-xl text-green-600 dark:text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_360 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Photos 360</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                            <i class="ri-vr-cardboard-line text-xl text-amber-600 dark:text-amber-400"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_tours }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Visites</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <i class="ri-hard-drive-2-line text-xl text-gray-600 dark:text-gray-400"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatBytes(stats.storage_used) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Stockage</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Site Filter -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site</label>
                        <select
                            v-model="filters.site_id"
                            @change="loadMedia"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option :value="null">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }} ({{ site.media_count }} medias)
                            </option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select
                            v-model="filters.type"
                            @change="loadMedia"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option :value="null">Tous</option>
                            <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="w-48">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categorie</label>
                        <select
                            v-model="filters.category"
                            @change="loadMedia"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option :value="null">Toutes</option>
                            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <!-- View Toggle -->
                    <div class="flex items-end gap-2">
                        <button
                            @click="viewMode = 'grid'"
                            :class="[
                                'p-2 rounded-lg transition-colors',
                                viewMode === 'grid'
                                    ? 'bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400'
                                    : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            <i class="ri-grid-fill text-xl"></i>
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            :class="[
                                'p-2 rounded-lg transition-colors',
                                viewMode === 'list'
                                    ? 'bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400'
                                    : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            <i class="ri-list-check text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Virtual Tours Section -->
            <div v-if="virtualTours.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="ri-vr-cardboard-line mr-2 text-amber-500"></i>
                    Visites Virtuelles 360
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="tour in virtualTours"
                        :key="tour.id"
                        class="group relative bg-gray-50 dark:bg-gray-700/50 rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                    >
                        <div class="aspect-video bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center">
                            <i class="ri-panorama-line text-6xl text-white/80"></i>
                        </div>
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ tour.name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ tour.panoramas_count }} panoramas
                            </p>
                            <div class="flex items-center gap-2 mt-3">
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
                                <span v-if="tour.is_public" class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    Public
                                </span>
                            </div>
                        </div>
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                            <button
                                @click="editTour(tour)"
                                class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-700 shadow"
                            >
                                <i class="ri-edit-line text-gray-600 dark:text-gray-300"></i>
                            </button>
                            <button
                                @click="deleteTour(tour)"
                                class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 shadow"
                            >
                                <i class="ri-delete-bin-line text-red-500"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Bibliotheque Media
                        <span class="text-sm font-normal text-gray-500">({{ media.total || 0 }} elements)</span>
                    </h2>
                    <div v-if="selectedMedia.length > 0" class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">{{ selectedMedia.length }} selectionne(s)</span>
                        <button
                            @click="bulkDelete"
                            class="px-3 py-1.5 bg-red-100 text-red-700 hover:bg-red-200 rounded-lg text-sm font-medium transition-colors"
                        >
                            <i class="ri-delete-bin-line mr-1"></i>
                            Supprimer
                        </button>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div
                        v-for="item in media.data"
                        :key="item.id"
                        class="group relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-primary-500"
                        :class="{ 'ring-2 ring-primary-500': selectedMedia.includes(item.id) }"
                        @click="toggleSelection(item)"
                    >
                        <!-- Thumbnail -->
                        <img
                            v-if="item.type === 'photo' || item.type === 'photo_360'"
                            :src="item.thumbnail_url || item.url"
                            :alt="item.alt_text || item.title"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        />
                        <div v-else-if="item.type === 'video'" class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-500 to-pink-500">
                            <i class="ri-play-circle-line text-4xl text-white"></i>
                        </div>
                        <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-amber-400 to-orange-500">
                            <i class="ri-panorama-line text-4xl text-white"></i>
                        </div>

                        <!-- Type Badge -->
                        <div class="absolute top-2 left-2">
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    item.type === 'photo' ? 'bg-blue-500 text-white' :
                                    item.type === 'video' ? 'bg-purple-500 text-white' :
                                    item.type === 'photo_360' ? 'bg-green-500 text-white' :
                                    'bg-amber-500 text-white'
                                ]"
                            >
                                <i :class="[
                                    item.type === 'photo' ? 'ri-image-line' :
                                    item.type === 'video' ? 'ri-video-line' :
                                    'ri-panorama-line'
                                ]"></i>
                            </span>
                        </div>

                        <!-- Featured Badge -->
                        <div v-if="item.is_featured" class="absolute top-2 right-2">
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-yellow-500 text-white">
                                <i class="ri-star-fill"></i>
                            </span>
                        </div>

                        <!-- Hover Actions -->
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button
                                @click.stop="viewMedia(item)"
                                class="p-2 bg-white rounded-full hover:bg-gray-100"
                            >
                                <i class="ri-eye-line text-gray-700"></i>
                            </button>
                            <button
                                @click.stop="editMedia(item)"
                                class="p-2 bg-white rounded-full hover:bg-gray-100"
                            >
                                <i class="ri-edit-line text-gray-700"></i>
                            </button>
                            <button
                                @click.stop="setFeatured(item)"
                                class="p-2 bg-white rounded-full hover:bg-yellow-100"
                            >
                                <i :class="['text-gray-700', item.is_featured ? 'ri-star-fill text-yellow-500' : 'ri-star-line']"></i>
                            </button>
                            <button
                                @click.stop="deleteMedia(item)"
                                class="p-2 bg-white rounded-full hover:bg-red-100"
                            >
                                <i class="ri-delete-bin-line text-red-500"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="item in media.data"
                        :key="item.id"
                        class="flex items-center gap-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg px-2"
                    >
                        <input
                            type="checkbox"
                            :checked="selectedMedia.includes(item.id)"
                            @change="toggleSelection(item)"
                            class="rounded text-primary-600"
                        />
                        <div class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                            <img
                                v-if="item.thumbnail_url"
                                :src="item.thumbnail_url"
                                :alt="item.title"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <i :class="[
                                    'text-2xl text-gray-400',
                                    item.type === 'photo' ? 'ri-image-line' :
                                    item.type === 'video' ? 'ri-video-line' :
                                    'ri-panorama-line'
                                ]"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate">
                                {{ item.title || item.original_name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ item.category_label }} &bull; {{ item.formatted_size }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    item.type === 'photo' ? 'bg-blue-100 text-blue-700' :
                                    item.type === 'video' ? 'bg-purple-100 text-purple-700' :
                                    item.type === 'photo_360' ? 'bg-green-100 text-green-700' :
                                    'bg-amber-100 text-amber-700'
                                ]"
                            >
                                {{ types[item.type] }}
                            </span>
                            <button @click="editMedia(item)" class="p-2 text-gray-400 hover:text-primary-600">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button @click="deleteMedia(item)" class="p-2 text-gray-400 hover:text-red-500">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!media.data || media.data.length === 0" class="py-12 text-center">
                    <i class="ri-image-line text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun media</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Commencez par telecharger des photos ou videos de vos sites
                    </p>
                    <button
                        @click="showUploadModal = true"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium"
                    >
                        <i class="ri-upload-cloud-2-line mr-2"></i>
                        Telecharger des fichiers
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="media.last_page > 1" class="flex items-center justify-center mt-6 gap-2">
                    <button
                        v-for="page in media.last_page"
                        :key="page"
                        @click="goToPage(page)"
                        :class="[
                            'w-10 h-10 rounded-lg font-medium transition-colors',
                            page === media.current_page
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                        ]"
                    >
                        {{ page }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <Teleport to="body">
            <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showUploadModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Telecharger des medias</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site *</label>
                                <select v-model="uploadForm.site_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categorie</label>
                                <select v-model="uploadForm.category" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="uploadForm.is_360" class="rounded text-primary-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Image 360 (panoramique)</span>
                                </label>
                            </div>

                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave="isDragging = false"
                                @drop.prevent="handleDrop"
                                :class="[
                                    'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                                    isDragging
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                        : 'border-gray-300 dark:border-gray-600 hover:border-primary-400'
                                ]"
                            >
                                <input
                                    type="file"
                                    ref="fileInput"
                                    multiple
                                    accept="image/*,video/*"
                                    @change="handleFileSelect"
                                    class="hidden"
                                />
                                <i class="ri-upload-cloud-2-line text-4xl text-gray-400 mb-2"></i>
                                <p class="text-gray-600 dark:text-gray-400 mb-2">
                                    Glissez-deposez vos fichiers ici ou
                                </p>
                                <button
                                    @click="$refs.fileInput.click()"
                                    class="text-primary-600 hover:text-primary-700 font-medium"
                                >
                                    Parcourir
                                </button>
                                <p class="text-xs text-gray-500 mt-2">
                                    JPG, PNG, GIF, WebP, MP4, WebM (max 50 Mo)
                                </p>
                            </div>

                            <!-- Selected Files -->
                            <div v-if="uploadForm.files.length > 0" class="space-y-2">
                                <div
                                    v-for="(file, index) in uploadForm.files"
                                    :key="index"
                                    class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                >
                                    <i :class="[
                                        'text-xl',
                                        file.type.startsWith('image/') ? 'ri-image-line text-blue-500' : 'ri-video-line text-purple-500'
                                    ]"></i>
                                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">{{ file.name }}</span>
                                    <span class="text-xs text-gray-500">{{ formatBytes(file.size) }}</span>
                                    <button @click="removeFile(index)" class="text-gray-400 hover:text-red-500">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                @click="showUploadModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Annuler
                            </button>
                            <button
                                @click="uploadFiles"
                                :disabled="uploading || uploadForm.files.length === 0"
                                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium disabled:opacity-50"
                            >
                                <i v-if="uploading" class="ri-loader-4-line animate-spin mr-2"></i>
                                {{ uploading ? 'Telechargement...' : 'Telecharger' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Tour Creation Modal -->
        <Teleport to="body">
            <div v-if="showTourModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showTourModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Nouvelle visite virtuelle 360</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site *</label>
                                <select v-model="tourForm.site_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom de la visite *</label>
                                <input
                                    v-model="tourForm.name"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="ex: Visite complete du site"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea
                                    v-model="tourForm.description"
                                    rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Description de la visite..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fournisseur externe (optionnel)</label>
                                <select v-model="tourForm.provider" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option :value="null">Aucun (utiliser vos propres photos)</option>
                                    <option value="matterport">Matterport</option>
                                    <option value="kuula">Kuula</option>
                                    <option value="cloudpano">CloudPano</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>

                            <div v-if="tourForm.provider">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code embed ou URL</label>
                                <textarea
                                    v-model="tourForm.embed_code"
                                    rows="3"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm"
                                    placeholder="<iframe src='...'></iframe> ou https://..."
                                ></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                @click="showTourModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                            >
                                Annuler
                            </button>
                            <button
                                @click="createTour"
                                :disabled="creatingTour || !tourForm.site_id || !tourForm.name"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50"
                            >
                                <i v-if="creatingTour" class="ri-loader-4-line animate-spin mr-2"></i>
                                Creer la visite
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Media Preview Modal -->
        <Teleport to="body">
            <div v-if="previewMedia" class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4">
                <button @click="previewMedia = null" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
                    <i class="ri-close-line"></i>
                </button>
                <img
                    v-if="previewMedia.type === 'photo' || previewMedia.type === 'photo_360'"
                    :src="previewMedia.url"
                    :alt="previewMedia.title"
                    class="max-w-full max-h-full object-contain"
                />
                <video
                    v-else-if="previewMedia.type === 'video'"
                    :src="previewMedia.url"
                    controls
                    autoplay
                    class="max-w-full max-h-full"
                ></video>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    sites: Array,
    selectedSiteId: Number,
    media: Object,
    virtualTours: Array,
    stats: Object,
    categories: Object,
    types: Object,
});

// State
const viewMode = ref('grid');
const showUploadModal = ref(false);
const showTourModal = ref(false);
const previewMedia = ref(null);
const selectedMedia = ref([]);
const isDragging = ref(false);
const uploading = ref(false);
const creatingTour = ref(false);

// Filters
const filters = ref({
    site_id: props.selectedSiteId,
    type: null,
    category: null,
});

// Forms
const uploadForm = ref({
    site_id: props.sites[0]?.id,
    category: 'other',
    is_360: false,
    files: [],
});

const tourForm = ref({
    site_id: props.sites[0]?.id,
    name: '',
    description: '',
    provider: null,
    embed_code: '',
});

// Methods
const formatBytes = (bytes) => {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }
    return bytes.toFixed(1) + ' ' + units[i];
};

const loadMedia = () => {
    router.get(route('tenant.media.index'), {
        site_id: filters.value.site_id,
        type: filters.value.type,
        category: filters.value.category,
    }, { preserveState: true });
};

const goToPage = (page) => {
    router.get(route('tenant.media.index'), {
        ...filters.value,
        page,
    }, { preserveState: true });
};

const toggleSelection = (item) => {
    const index = selectedMedia.value.indexOf(item.id);
    if (index === -1) {
        selectedMedia.value.push(item.id);
    } else {
        selectedMedia.value.splice(index, 1);
    }
};

const handleFileSelect = (e) => {
    uploadForm.value.files = [...uploadForm.value.files, ...e.target.files];
};

const handleDrop = (e) => {
    isDragging.value = false;
    uploadForm.value.files = [...uploadForm.value.files, ...e.dataTransfer.files];
};

const removeFile = (index) => {
    uploadForm.value.files.splice(index, 1);
};

const uploadFiles = async () => {
    uploading.value = true;

    const formData = new FormData();
    formData.append('site_id', uploadForm.value.site_id);
    formData.append('category', uploadForm.value.category);
    formData.append('is_360', uploadForm.value.is_360 ? '1' : '0');
    uploadForm.value.files.forEach((file, i) => {
        formData.append(`files[${i}]`, file);
    });

    try {
        await axios.post(route('tenant.media.upload'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        showUploadModal.value = false;
        uploadForm.value.files = [];
        router.reload();
    } catch (error) {
        console.error('Upload error:', error);
        alert('Erreur lors du telechargement');
    } finally {
        uploading.value = false;
    }
};

const viewMedia = (item) => {
    previewMedia.value = item;
};

const editMedia = (item) => {
    // TODO: Open edit modal
    console.log('Edit media:', item);
};

const setFeatured = async (item) => {
    try {
        await axios.post(route('tenant.media.set-featured', item.id));
        router.reload();
    } catch (error) {
        console.error('Error:', error);
    }
};

const deleteMedia = async (item) => {
    if (!confirm('Supprimer ce media ?')) return;

    try {
        await axios.delete(route('tenant.media.destroy', item.id));
        router.reload();
    } catch (error) {
        console.error('Error:', error);
    }
};

const bulkDelete = async () => {
    if (!confirm(`Supprimer ${selectedMedia.value.length} element(s) ?`)) return;

    try {
        await axios.post(route('tenant.media.bulk-delete'), {
            media_ids: selectedMedia.value
        });
        selectedMedia.value = [];
        router.reload();
    } catch (error) {
        console.error('Error:', error);
    }
};

const createTour = async () => {
    creatingTour.value = true;

    try {
        const response = await axios.post(route('tenant.media.tours.store'), tourForm.value);
        showTourModal.value = false;
        tourForm.value = { site_id: props.sites[0]?.id, name: '', description: '', provider: null, embed_code: '' };

        // Redirect to tour editor if custom tour
        if (!tourForm.value.provider) {
            router.visit(route('tenant.media.tours.edit', response.data.tour.id));
        } else {
            router.reload();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors de la creation de la visite');
    } finally {
        creatingTour.value = false;
    }
};

const editTour = (tour) => {
    router.visit(route('tenant.media.tours.edit', tour.id));
};

const deleteTour = async (tour) => {
    if (!confirm('Supprimer cette visite virtuelle ?')) return;

    try {
        await axios.delete(route('tenant.media.tours.destroy', tour.id));
        router.reload();
    } catch (error) {
        console.error('Error:', error);
    }
};
</script>
