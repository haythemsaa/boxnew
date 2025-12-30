<template>
    <PublicLayout :seo="seo">
        <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
            <!-- Hero Section -->
            <section class="relative py-16 bg-gradient-to-r from-indigo-600 to-purple-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <Link :href="route('blog.index')" class="inline-flex items-center gap-2 text-white/80 hover:text-white mb-4 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ t('blog.back_to_blog') }}
                    </Link>
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">
                        {{ category.name }}
                    </h1>
                    <p v-if="category.description" class="text-xl text-indigo-100 max-w-3xl">
                        {{ category.description }}
                    </p>
                </div>
            </section>

            <!-- Main Content -->
            <section class="py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-4 gap-12">
                        <!-- Posts Grid -->
                        <div class="lg:col-span-3">
                            <div class="mb-6 text-gray-600">
                                {{ posts.total }} {{ t('blog.articles_in_category') }}
                            </div>

                            <div v-if="posts.data && posts.data.length > 0" class="grid md:grid-cols-2 gap-8">
                                <article
                                    v-for="post in posts.data"
                                    :key="post.id"
                                    class="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300"
                                >
                                    <Link :href="post.url">
                                        <div class="h-48 overflow-hidden">
                                            <img
                                                v-if="post.featured_image"
                                                :src="post.featured_image"
                                                :alt="post.featured_image_alt || post.title"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            >
                                            <div v-else class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                                        </div>
                                    </Link>
                                    <div class="p-6">
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                                            <span>{{ post.formatted_date }}</span>
                                            <span>&bull;</span>
                                            <span>{{ post.reading_time }} min</span>
                                        </div>
                                        <Link :href="post.url">
                                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2 line-clamp-2">
                                                {{ post.title }}
                                            </h3>
                                        </Link>
                                        <p class="text-gray-600 text-sm line-clamp-3">
                                            {{ post.excerpt || truncate(stripHtml(post.content), 150) }}
                                        </p>
                                        <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-2 mt-4">
                                            <Link
                                                v-for="tag in post.tags.slice(0, 3)"
                                                :key="tag.id"
                                                :href="route('blog.tag', tag.slug)"
                                                class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1 rounded-full transition-colors"
                                            >
                                                #{{ tag.name }}
                                            </Link>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="text-center py-16 bg-gray-50 rounded-2xl">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                                <p class="text-gray-500">{{ t('blog.no_posts_category') }}</p>
                            </div>

                            <!-- Pagination -->
                            <div v-if="posts.data && posts.data.length > 0" class="mt-12 flex justify-center">
                                <nav class="flex items-center gap-2">
                                    <Link
                                        v-for="link in posts.links"
                                        :key="link.label"
                                        :href="link.url"
                                        :class="[
                                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : link.url
                                                    ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                    : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <aside class="lg:col-span-1">
                            <div class="bg-gray-50 rounded-2xl p-6 sticky top-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('blog.other_categories') }}</h3>
                                <div class="space-y-2">
                                    <Link
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        :href="route('blog.category', cat.slug)"
                                        :class="[
                                            'flex items-center justify-between p-3 rounded-lg transition-colors',
                                            cat.id === category.id
                                                ? 'bg-indigo-100 text-indigo-700'
                                                : 'hover:bg-white text-gray-700 hover:text-indigo-600'
                                        ]"
                                    >
                                        <span class="font-medium">{{ cat.name }}</span>
                                        <span :class="[
                                            'text-xs font-medium px-2 py-1 rounded-full',
                                            cat.id === category.id
                                                ? 'bg-indigo-200 text-indigo-700'
                                                : 'bg-gray-200 text-gray-600'
                                        ]">
                                            {{ cat.published_posts_count }}
                                        </span>
                                    </Link>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { useTranslation } from '@/composables/useTranslation'

const { t } = useTranslation()

defineProps({
    category: Object,
    posts: Object,
    categories: Array,
    seo: Object,
})

const stripHtml = (html) => {
    if (!html) return ''
    return html.replace(/<[^>]*>/g, '')
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}
</script>
