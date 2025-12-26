<template>
    <PublicLayout>
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-12 mb-8">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold mb-4">Trouvez Votre Espace de Stockage Idéal</h1>
                <p class="text-xl">Solutions de stockage sécurisées, abordables et pratiques</p>
            </div>
        </div>

        <div class="w-full px-4 sm:px-6 lg:px-8 pb-12">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtrer les Box</h2>
                <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                        <select
                            v-model="filterForm.site_id"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }} - {{ site.city }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Taille</label>
                        <select
                            v-model="filterForm.size_category"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">Toutes tailles</option>
                            <option value="small">Petit (≤5 m³)</option>
                            <option value="medium">Moyen (5-15 m³)</option>
                            <option value="large">Grand (15-25 m³)</option>
                            <option value="extra_large">Très Grand (>25 m³)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix Max</label>
                        <input
                            v-model="filterForm.max_price"
                            type="number"
                            placeholder="€/mois"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trier par</label>
                        <select
                            v-model="filterForm.sort_by"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="price_asc">Prix croissant</option>
                            <option value="price_desc">Prix décroissant</option>
                            <option value="size_asc">Taille croissante</option>
                            <option value="size_desc">Taille décroissante</option>
                        </select>
                    </div>

                    <div class="md:col-span-4 flex justify-end">
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Rechercher
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    Box Disponibles
                    <span class="text-gray-500 text-lg">({{ boxes.total }} trouvés)</span>
                </h2>
            </div>

            <!-- Box Grid -->
            <div v-if="boxes.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                <div
                    v-for="box in boxes.data"
                    :key="box.id"
                    class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all hover:-translate-y-1 border border-gray-100"
                >
                    <div class="p-4">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Box {{ box.number }}</h3>
                                <p class="text-sm text-gray-600">{{ box.site.name }}</p>
                            </div>
                            <span class="px-2 py-0.5 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                Dispo
                            </span>
                        </div>

                        <!-- Specs - Horizontal compact layout -->
                        <div class="flex flex-wrap gap-2 mb-3 text-sm">
                            <span class="flex items-center gap-1 px-2 py-1 bg-gray-100 rounded-lg text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                {{ box.length }}x{{ box.width }}x{{ box.height }}m
                            </span>
                            <span class="flex items-center gap-1 px-2 py-1 bg-blue-50 rounded-lg text-blue-700 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                {{ formatVolume(box.volume) }}m³
                            </span>
                        </div>

                        <!-- Size category -->
                        <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded mb-3">
                            {{ getSizeLabel(box.volume) }}
                        </span>

                        <!-- Price & Action -->
                        <div class="border-t pt-3 flex justify-between items-center">
                            <div>
                                <span class="text-2xl font-bold text-blue-600">{{ box.current_price }}€</span>
                                <span class="text-xs text-gray-500">/mois</span>
                            </div>
                            <Link
                                :href="route('booking.show', box.id)"
                                class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                Réserver
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun box disponible</h3>
                <p class="mt-1 text-sm text-gray-500">Essayez de modifier vos critères de recherche</p>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    sites: Array,
    boxes: Object,
    filters: Object,
});

const filterForm = ref({
    site_id: props.filters.site_id || '',
    size_category: props.filters.size_category || '',
    max_price: props.filters.max_price || '',
    sort_by: props.filters.sort_by || 'price_asc',
});

const search = () => {
    router.get(route('booking.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatVolume = (volume) => {
    if (volume === null || volume === undefined) return '0.00';
    return parseFloat(volume).toFixed(2);
};

const getSizeLabel = (volume) => {
    if (!volume) return 'N/A';
    const v = parseFloat(volume);
    if (v <= 5) return 'Petit';
    if (v <= 15) return 'Moyen';
    if (v <= 25) return 'Grand';
    return 'Très Grand';
};

const formatSizeCategory = (category) => {
    const map = {
        small: 'Petit',
        medium: 'Moyen',
        large: 'Grand',
        extra_large: 'Très Grand',
    };
    return map[category] || category;
};
</script>
