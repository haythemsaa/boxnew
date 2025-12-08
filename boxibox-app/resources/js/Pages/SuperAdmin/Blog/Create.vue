<template>
    <SuperAdminLayout title="Nouvel Article">
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <Link :href="route('superadmin.blog.index')" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-2">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">Nouvel Article</h1>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <div class="grid lg:grid-cols-3 gap-8">
                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Title -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Titre *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    required
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Entrez le titre de l'article"
                                >
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                            </div>

                            <!-- Slug -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Slug (URL)</label>
                                <div class="flex items-center">
                                    <span class="text-gray-500 text-sm mr-2">/blog/</span>
                                    <input
                                        v-model="form.slug"
                                        type="text"
                                        class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="auto-generated-from-title"
                                    >
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Laissez vide pour générer automatiquement</p>
                            </div>

                            <!-- Excerpt -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Extrait</label>
                                <textarea
                                    v-model="form.excerpt"
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Court résumé de l'article (affiché dans les listes)"
                                ></textarea>
                            </div>

                            <!-- Content -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contenu *</label>
                                <textarea
                                    v-model="form.content"
                                    rows="20"
                                    required
                                    class="w-full px-4 py-3 border border-gray-200 rounded-lg font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Contenu de l'article (HTML supporté)"
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">HTML et Markdown supportés</p>
                            </div>

                            <!-- SEO Section -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">SEO & Meta</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                                        <input
                                            v-model="form.meta_title"
                                            type="text"
                                            maxlength="70"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Titre pour les moteurs de recherche"
                                        >
                                        <p class="text-xs text-gray-500 mt-1">{{ form.meta_title?.length || 0 }}/70 caractères</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                                        <textarea
                                            v-model="form.meta_description"
                                            rows="2"
                                            maxlength="160"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Description pour les moteurs de recherche"
                                        ></textarea>
                                        <p class="text-xs text-gray-500 mt-1">{{ form.meta_description?.length || 0 }}/160 caractères</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                                        <input
                                            v-model="form.meta_keywords"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="mot-clé1, mot-clé2, mot-clé3"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Canonique</label>
                                        <input
                                            v-model="form.canonical_url"
                                            type="url"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="https://..."
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Open Graph -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Open Graph (Réseaux Sociaux)</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                                        <input
                                            v-model="form.og_title"
                                            type="text"
                                            maxlength="70"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Titre pour les réseaux sociaux"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                                        <textarea
                                            v-model="form.og_description"
                                            rows="2"
                                            maxlength="200"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Description pour les réseaux sociaux"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="space-y-6">
                            <!-- Publish Settings -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Publication</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Statut *</label>
                                        <select
                                            v-model="form.status"
                                            required
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="draft">Brouillon</option>
                                            <option value="published">Publié</option>
                                            <option value="scheduled">Programmé</option>
                                        </select>
                                    </div>
                                    <div v-if="form.status === 'scheduled'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de publication</label>
                                        <input
                                            v-model="form.scheduled_at"
                                            type="datetime-local"
                                            required
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Langue</label>
                                        <select
                                            v-model="form.locale"
                                            required
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option v-for="loc in locales" :key="loc.code" :value="loc.code">
                                                {{ loc.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Category & Author -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Organisation</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                                        <select
                                            v-model="form.category_id"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="">Aucune</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                {{ cat.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Auteur</label>
                                        <select
                                            v-model="form.author_id"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="">Moi-même</option>
                                            <option v-for="author in authors" :key="author.id" :value="author.id">
                                                {{ author.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Tags</h3>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        :class="[
                                            'cursor-pointer px-3 py-1.5 rounded-full text-sm transition-colors',
                                            form.tags.includes(tag.id)
                                                ? 'bg-indigo-100 text-indigo-700 border-2 border-indigo-500'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border-2 border-transparent'
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="tag.id"
                                            v-model="form.tags"
                                            class="hidden"
                                        >
                                        #{{ tag.name }}
                                    </label>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Image à la Une</h3>
                                <div class="space-y-4">
                                    <div
                                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-indigo-400 transition-colors"
                                        @click="$refs.featuredImage.click()"
                                    >
                                        <img
                                            v-if="imagePreview"
                                            :src="imagePreview"
                                            class="w-full h-40 object-cover rounded-lg mb-2"
                                        >
                                        <svg v-else class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-sm text-gray-500">Cliquez pour télécharger</p>
                                        <input
                                            ref="featuredImage"
                                            type="file"
                                            accept="image/*"
                                            @change="handleImageUpload"
                                            class="hidden"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Texte alternatif</label>
                                        <input
                                            v-model="form.featured_image_alt"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Description de l'image"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Options -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Options</h3>
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_featured"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">Article à la une</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.allow_comments"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">Autoriser les commentaires</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="flex gap-3">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                                <Link
                                    :href="route('superadmin.blog.index')"
                                    class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
                                >
                                    Annuler
                                </Link>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'

const props = defineProps({
    categories: Array,
    tags: Array,
    authors: Array,
    locales: Array,
})

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    content: '',
    category_id: '',
    author_id: '',
    featured_image: null,
    featured_image_alt: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    canonical_url: '',
    og_title: '',
    og_description: '',
    status: 'draft',
    scheduled_at: '',
    locale: 'fr',
    is_featured: false,
    allow_comments: true,
    tags: [],
})

const imagePreview = ref(null)

const handleImageUpload = (e) => {
    const file = e.target.files[0]
    if (file) {
        form.featured_image = file
        imagePreview.value = URL.createObjectURL(file)
    }
}

const submit = () => {
    form.post(route('superadmin.blog.store'), {
        forceFormData: true,
    })
}
</script>
