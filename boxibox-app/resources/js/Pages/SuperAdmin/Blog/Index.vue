<template>
    <SuperAdminLayout title="Gestion du Blog">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Gestion du Blog</h1>
                        <p class="text-gray-600 mt-1">Gérez vos articles, catégories et commentaires</p>
                    </div>
                    <div class="flex gap-3 mt-4 md:mt-0">
                        <Link
                            :href="route('superadmin.blog.categories')"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Catégories
                        </Link>
                        <Link
                            :href="route('superadmin.blog.tags')"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            Tags
                        </Link>
                        <Link
                            :href="route('superadmin.blog.comments')"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Commentaires
                        </Link>
                        <Link
                            :href="route('superadmin.blog.create')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouvel Article
                        </Link>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="text-3xl font-bold text-gray-900">{{ stats.total }}</div>
                        <div class="text-gray-600 text-sm">Total articles</div>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="text-3xl font-bold text-green-600">{{ stats.published }}</div>
                        <div class="text-gray-600 text-sm">Publiés</div>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="text-3xl font-bold text-yellow-600">{{ stats.draft }}</div>
                        <div class="text-gray-600 text-sm">Brouillons</div>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="text-3xl font-bold text-blue-600">{{ stats.scheduled }}</div>
                        <div class="text-gray-600 text-sm">Programmés</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Rechercher un article..."
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                @keyup.enter="applyFilters"
                            >
                        </div>
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="draft">Brouillon</option>
                            <option value="published">Publié</option>
                            <option value="scheduled">Programmé</option>
                            <option value="archived">Archivé</option>
                        </select>
                        <select
                            v-model="categoryFilter"
                            @change="applyFilters"
                            class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">Toutes les catégories</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Posts Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Article
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Catégorie
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Auteur
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Vues
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-12 w-16 flex-shrink-0 mr-4">
                                            <img
                                                v-if="post.featured_image"
                                                :src="post.featured_image"
                                                :alt="post.title"
                                                class="h-12 w-16 rounded-lg object-cover"
                                            >
                                            <div v-else class="h-12 w-16 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 line-clamp-1">
                                                {{ post.title }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ post.reading_time }} min de lecture
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="post.category" class="text-sm text-gray-900">
                                        {{ post.category.name }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="post.author" class="text-sm text-gray-900">
                                        {{ post.author.name }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="statusClass(post.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
                                        {{ statusLabel(post.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ post.views_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(post.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            :href="route('blog.show', post.slug)"
                                            target="_blank"
                                            class="text-gray-400 hover:text-gray-600"
                                            title="Voir"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <Link
                                            :href="route('superadmin.blog.edit', post.id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="Modifier"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>
                                        <button
                                            v-if="post.status === 'draft'"
                                            @click="publishPost(post)"
                                            class="text-green-600 hover:text-green-900"
                                            title="Publier"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deletePost(post)"
                                            class="text-red-600 hover:text-red-900"
                                            title="Supprimer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="posts.data && posts.data.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                        <p class="text-gray-500 mb-4">Aucun article trouvé</p>
                        <Link
                            :href="route('superadmin.blog.create')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                        >
                            Créer votre premier article
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div v-if="posts.data && posts.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                        <nav class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Affichage de {{ posts.from }} à {{ posts.to }} sur {{ posts.total }} articles
                            </div>
                            <div class="flex gap-2">
                                <Link
                                    v-for="link in posts.links"
                                    :key="link.label"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-1 rounded text-sm',
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : link.url
                                                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'

const props = defineProps({
    posts: Object,
    categories: Array,
    stats: Object,
    filters: Object,
})

const searchQuery = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const categoryFilter = ref(props.filters?.category || '')

const applyFilters = () => {
    router.get(route('superadmin.blog.index'), {
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        category: categoryFilter.value || undefined,
    }, { preserveState: true })
}

const statusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700',
        published: 'bg-green-100 text-green-700',
        scheduled: 'bg-blue-100 text-blue-700',
        archived: 'bg-yellow-100 text-yellow-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const statusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        published: 'Publié',
        scheduled: 'Programmé',
        archived: 'Archivé',
    }
    return labels[status] || status
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const publishPost = (post) => {
    if (confirm('Publier cet article ?')) {
        router.post(route('superadmin.blog.publish', post.id))
    }
}

const deletePost = (post) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        router.delete(route('superadmin.blog.destroy', post.id))
    }
}
</script>
