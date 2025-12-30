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
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-white/20 backdrop-blur-sm text-white px-4 py-2 rounded-full text-lg font-medium">
                            #{{ tag.name }}
                        </span>
                    </div>
                    <p class="text-xl text-indigo-100">
                        {{ posts.total }} {{ t('blog.articles_tagged') }}
                    </p>
                </div>
            </section>

            <!-- Posts Grid -->
            <section class="py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div v-if="posts.data && posts.data.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
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
                                    <span v-if="post.category" class="text-indigo-600 font-medium">
                                        {{ post.category.name }}
                                    </span>
                                    <span v-if="post.category">&bull;</span>
                                    <span>{{ post.formatted_date }}</span>
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
                                        v-for="t in post.tags.slice(0, 3)"
                                        :key="t.id"
                                        :href="route('blog.tag', t.slug)"
                                        :class="[
                                            'text-xs px-2 py-1 rounded-full transition-colors',
                                            t.id === tag.id
                                                ? 'bg-indigo-100 text-indigo-700'
                                                : 'bg-gray-100 hover:bg-gray-200 text-gray-600'
                                        ]"
                                    >
                                        #{{ t.name }}
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="text-center py-16 bg-gray-50 rounded-2xl">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <p class="text-gray-500">{{ t('blog.no_posts_tag') }}</p>
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
    tag: Object,
    posts: Object,
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
