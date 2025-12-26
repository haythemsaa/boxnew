<template>
    <PublicLayout :title="`Box ${box.number || box.name}`">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-12">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <Link
                    :href="route('booking.index')"
                    class="text-blue-600 hover:text-blue-800"
                >
                    ← Retour à la recherche
                </Link>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Box {{ box.number || box.name }}</h1>
                                <p class="text-lg text-gray-600 mt-2">{{ box.site?.name }}</p>
                                <p class="text-gray-500">{{ box.site?.address }}, {{ box.site?.city }}</p>
                            </div>
                            <span class="px-4 py-2 bg-green-100 text-green-800 font-semibold rounded-full">
                                Disponible
                            </span>
                        </div>

                        <!-- Specifications -->
                        <div class="border-t border-b py-6 my-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Caractéristiques</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Dimensions</p>
                                    <p class="text-lg font-medium">{{ box.length }} x {{ box.width }} x {{ box.height }} m</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Volume</p>
                                    <p class="text-lg font-medium">{{ formatVolume(box.volume) }} m³</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Catégorie</p>
                                    <p class="text-lg font-medium">{{ getSizeLabel(box.volume) }}</p>
                                </div>
                                <div v-if="box.floor">
                                    <p class="text-sm text-gray-600">Étage</p>
                                    <p class="text-lg font-medium">{{ box.floor }}</p>
                                </div>
                                <div v-if="box.zone">
                                    <p class="text-sm text-gray-600">Zone</p>
                                    <p class="text-lg font-medium">{{ box.zone }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">État</p>
                                    <p class="text-lg font-medium capitalize">{{ box.condition || 'Bon état' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div v-if="box.features && box.features.length > 0" class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Équipements</h2>
                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    v-for="feature in box.features"
                                    :key="feature"
                                    class="flex items-center"
                                >
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700">{{ feature }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Site Information -->
                        <div class="border-t pt-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations du Site</h2>
                            <div class="space-y-2">
                                <div>
                                    <p class="text-sm text-gray-600">Adresse</p>
                                    <p class="text-gray-900">{{ box.site?.address }}</p>
                                    <p class="text-gray-900">{{ box.site?.postal_code }} {{ box.site?.city }}, {{ box.site?.country }}</p>
                                </div>
                                <div v-if="box.site?.phone">
                                    <p class="text-sm text-gray-600">Téléphone</p>
                                    <p class="text-gray-900">{{ box.site.phone }}</p>
                                </div>
                                <div v-if="box.site?.email">
                                    <p class="text-sm text-gray-600">Email</p>
                                    <p class="text-gray-900">{{ box.site.email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Similar Units -->
                    <div v-if="similarBoxes && similarBoxes.length > 0" class="mt-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Box Similaires</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div
                                v-for="similarBox in similarBoxes"
                                :key="similarBox.id"
                                class="bg-white rounded-lg shadow-md p-4"
                            >
                                <h3 class="font-semibold text-gray-900">Box {{ similarBox.number || similarBox.name }}</h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    {{ similarBox.length }} x {{ similarBox.width }} x {{ similarBox.height }} m
                                </p>
                                <div class="flex justify-between items-center">
                                    <p class="text-xl font-bold text-blue-600">{{ formatPrice(similarBox.current_price) }}€/mois</p>
                                    <Link
                                        :href="route('booking.show', similarBox.id)"
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        Voir →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Card (Sticky) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <div class="text-center mb-6">
                            <p class="text-4xl font-bold text-blue-600">{{ formatPrice(box.current_price) }}€</p>
                            <p class="text-gray-600">/mois</p>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Prix mensuel:</span>
                                <span class="font-medium">{{ formatPrice(box.current_price) }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Dépôt de garantie:</span>
                                <span class="font-medium">{{ formatPrice(box.current_price) }}€</span>
                            </div>
                            <div class="border-t pt-3 flex justify-between">
                                <span class="font-semibold">À payer:</span>
                                <span class="font-bold text-lg">{{ formatPrice(parseFloat(box.current_price || 0) * 2) }}€</span>
                            </div>
                        </div>

                        <Link
                            :href="route('booking.checkout', box.id)"
                            class="block w-full py-3 px-4 bg-blue-600 text-white text-center font-semibold rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Réserver Maintenant
                        </Link>

                        <div class="mt-6 pt-6 border-t space-y-3">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Emménagez quand vous voulez</p>
                                    <p class="text-sm text-gray-600">Choisissez votre date d'entrée</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Sans engagement</p>
                                    <p class="text-sm text-gray-600">Résiliez à tout moment</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Sécurisé & Surveillé</p>
                                    <p class="text-sm text-gray-600">Surveillance 24h/24</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    box: Object,
    similarBoxes: Array,
});

const formatVolume = (volume) => {
    if (volume === null || volume === undefined) return '0.00';
    return parseFloat(volume).toFixed(2);
};

const formatPrice = (price) => {
    if (price === null || price === undefined) return '0.00';
    return parseFloat(price).toFixed(2);
};

const getSizeLabel = (volume) => {
    if (!volume) return 'N/A';
    const v = parseFloat(volume);
    if (v <= 5) return 'Petit';
    if (v <= 15) return 'Moyen';
    if (v <= 25) return 'Grand';
    return 'Très Grand';
};
</script>
