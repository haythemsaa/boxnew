<template>
    <PublicLayout :seo="seo">
        <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
            <!-- Hero Section -->
            <section class="relative py-20 bg-gradient-to-r from-indigo-600 to-purple-700">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute inset-0 bg-[url('/images/pattern.svg')] opacity-10"></div>
                </div>
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        {{ t('blog.title') }}
                    </h1>
                    <p class="text-xl text-indigo-100 max-w-3xl mx-auto mb-8">
                        {{ t('blog.subtitle') }}
                    </p>

                    <!-- Search -->
                    <form @submit.prevent="search" class="max-w-2xl mx-auto">
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="t('blog.search_placeholder')"
                                class="w-full px-6 py-4 rounded-full text-gray-900 bg-white shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-300"
                            >
                            <button
                                type="submit"
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-600 text-white p-3 rounded-full hover:bg-indigo-700 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Featured Posts -->
            <section v-if="featuredPosts && featuredPosts.length > 0" class="py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8">{{ t('blog.featured') }}</h2>
                    <div class="grid md:grid-cols-3 gap-8">
                        <article
                            v-for="post in featuredPosts"
                            :key="post.id"
                            class="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300"
                        >
                            <Link :href="post.url" class="block">
                                <div class="relative h-48 overflow-hidden">
                                    <img
                                        v-if="post.featured_image"
                                        :src="post.featured_image"
                                        :alt="post.featured_image_alt || post.title"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    >
                                    <div v-else class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                                    <span class="absolute top-4 left-4 bg-yellow-400 text-yellow-900 text-xs font-semibold px-3 py-1 rounded-full">
                                        {{ t('blog.featured_badge') }}
                                    </span>
                                </div>
                                <div class="p-6">
                                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                                        <span v-if="post.category" class="text-indigo-600 font-medium">
                                            {{ post.category.name }}
                                        </span>
                                        <span>&bull;</span>
                                        <span>{{ post.formatted_date }}</span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2">
                                        {{ post.title }}
                                    </h3>
                                    <p class="text-gray-600 text-sm line-clamp-2">
                                        {{ post.excerpt || truncate(stripHtml(post.content), 120) }}
                                    </p>
                                </div>
                            </Link>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Main Content -->
            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid lg:grid-cols-4 gap-12">
                        <!-- Posts Grid -->
                        <div class="lg:col-span-3">
                            <div class="flex items-center justify-between mb-8">
                                <h2 class="text-2xl font-bold text-gray-900">{{ t('blog.latest') }}</h2>
                                <Link :href="route('blog.rss')" class="flex items-center gap-2 text-orange-500 hover:text-orange-600">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6.18 15.64a2.18 2.18 0 010 4.36 2.18 2.18 0 010-4.36m-3.6-5.09v3.57c3.9 0 7.07 3.17 7.07 7.07h3.57c0-5.87-4.77-10.64-10.64-10.64m0-5.09v3.57c7.8 0 14.16 6.36 14.16 14.16h3.57c0-9.77-7.96-17.73-17.73-17.73" />
                                    </svg>
                                    <span class="text-sm font-medium">RSS</span>
                                </Link>
                            </div>

                            <!-- Posts List -->
                            <div v-if="posts.data && posts.data.length > 0" class="space-y-8">
                                <article
                                    v-for="post in posts.data"
                                    :key="post.id"
                                    class="group flex flex-col md:flex-row gap-6 p-6 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors"
                                >
                                    <Link :href="post.url" class="md:w-64 flex-shrink-0">
                                        <div class="h-40 md:h-full rounded-xl overflow-hidden">
                                            <img
                                                v-if="post.featured_image"
                                                :src="post.featured_image"
                                                :alt="post.featured_image_alt || post.title"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            >
                                            <div v-else class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                                        </div>
                                    </Link>
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 mb-3">
                                            <span v-if="post.category" class="text-indigo-600 font-medium">
                                                {{ post.category.name }}
                                            </span>
                                            <span>&bull;</span>
                                            <span>{{ post.formatted_date }}</span>
                                            <span>&bull;</span>
                                            <span>{{ post.reading_time }} min {{ t('blog.read') }}</span>
                                        </div>
                                        <Link :href="post.url">
                                            <h3 class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2">
                                                {{ post.title }}
                                            </h3>
                                        </Link>
                                        <p class="text-gray-600 mb-4 line-clamp-2">
                                            {{ post.excerpt || truncate(stripHtml(post.content), 180) }}
                                        </p>
                                        <div class="flex items-center gap-4">
                                            <div v-if="post.author" class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-indigo-600 font-medium text-sm">
                                                        {{ post.author.name.charAt(0) }}
                                                    </span>
                                                </div>
                                                <span class="text-sm text-gray-600">{{ post.author.name }}</span>
                                            </div>
                                            <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-2">
                                                <Link
                                                    v-for="tag in post.tags.slice(0, 3)"
                                                    :key="tag.id"
                                                    :href="route('blog.tag', tag.slug)"
                                                    class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded-full transition-colors"
                                                >
                                                    #{{ tag.name }}
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <!-- Empty State -->
                            <div v-else class="text-center py-16">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                </svg>
                                <p class="text-gray-500">{{ t('blog.no_posts') }}</p>
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
                        <aside class="lg:col-span-1 space-y-8">
                            <!-- Categories -->
                            <div class="bg-gray-50 rounded-2xl p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('blog.categories') }}</h3>
                                <div class="space-y-2">
                                    <Link
                                        v-for="category in categories"
                                        :key="category.id"
                                        :href="route('blog.category', category.slug)"
                                        class="flex items-center justify-between p-3 rounded-lg hover:bg-white transition-colors group"
                                    >
                                        <span class="text-gray-700 group-hover:text-indigo-600 transition-colors">
                                            {{ category.name }}
                                        </span>
                                        <span class="bg-gray-200 group-hover:bg-indigo-100 text-gray-600 group-hover:text-indigo-600 text-xs font-medium px-2 py-1 rounded-full transition-colors">
                                            {{ category.published_posts_count }}
                                        </span>
                                    </Link>
                                </div>
                            </div>

                            <!-- Popular Tags -->
                            <div class="bg-gray-50 rounded-2xl p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('blog.tags') }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        v-for="tag in popularTags"
                                        :key="tag.id"
                                        :href="route('blog.tag', tag.slug)"
                                        class="bg-white hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 text-sm px-3 py-1.5 rounded-full border border-gray-200 hover:border-indigo-200 transition-colors"
                                    >
                                        #{{ tag.name }}
                                    </Link>
                                </div>
                            </div>

                            <!-- Newsletter -->
                            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 text-white">
                                <h3 class="text-lg font-semibold mb-2">{{ t('blog.newsletter_title') }}</h3>
                                <p class="text-indigo-100 text-sm mb-4">{{ t('blog.newsletter_desc') }}</p>
                                <form @submit.prevent="subscribeNewsletter" class="space-y-3">
                                    <input
                                        v-model="newsletterEmail"
                                        type="email"
                                        :placeholder="t('blog.newsletter_placeholder')"
                                        class="w-full px-4 py-2.5 rounded-lg bg-white/10 border border-white/20 text-white placeholder-indigo-200 focus:outline-none focus:border-white/40"
                                        required
                                    >
                                    <button
                                        type="submit"
                                        class="w-full bg-white text-indigo-600 font-medium py-2.5 rounded-lg hover:bg-indigo-50 transition-colors"
                                    >
                                        {{ t('blog.newsletter_button') }}
                                    </button>
                                </form>
                            </div>
                        </aside>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { useTranslation } from '@/composables/useTranslation'

const { t } = useTranslation()

const props = defineProps({
    posts: Object,
    categories: Array,
    featuredPosts: Array,
    popularTags: Array,
    filters: Object,
    seo: Object,
})

const searchQuery = ref(props.filters?.search || '')
const newsletterEmail = ref('')

const search = () => {
    router.get(route('blog.index'), { search: searchQuery.value }, { preserveState: true })
}

const subscribeNewsletter = () => {
    // TODO: Implement newsletter subscription
    alert(t('blog.newsletter_success'))
    newsletterEmail.value = ''
}

const stripHtml = (html) => {
    if (!html) return ''
    return html.replace(/<[^>]*>/g, '')
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}
</script>
