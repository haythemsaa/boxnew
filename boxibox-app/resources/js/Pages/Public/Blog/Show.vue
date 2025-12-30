<template>
    <PublicLayout :seo="seo">
        <article class="min-h-screen bg-white">
            <!-- Hero Image -->
            <div class="relative h-[50vh] min-h-[400px] bg-gradient-to-r from-indigo-600 to-purple-700">
                <img
                    v-if="post.featured_image"
                    :src="post.featured_image"
                    :alt="post.featured_image_alt || post.title"
                    class="w-full h-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

                <!-- Article Header -->
                <div class="absolute bottom-0 left-0 right-0 p-8">
                    <div class="max-w-4xl mx-auto">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <Link
                                v-if="post.category"
                                :href="route('blog.category', post.category.slug)"
                                class="bg-indigo-600 text-white text-sm font-medium px-4 py-1.5 rounded-full hover:bg-indigo-700 transition-colors"
                            >
                                {{ post.category.name }}
                            </Link>
                            <span class="text-white/80 text-sm">{{ post.formatted_date }}</span>
                            <span class="text-white/60">&bull;</span>
                            <span class="text-white/80 text-sm">{{ post.reading_time }} min {{ t('blog.read') }}</span>
                            <span class="text-white/60">&bull;</span>
                            <span class="text-white/80 text-sm">{{ post.views_count }} {{ t('blog.views') }}</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                            {{ post.title }}
                        </h1>
                        <div v-if="post.author" class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="text-white font-semibold text-lg">
                                    {{ post.author.name.charAt(0) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-white font-medium">{{ post.author.name }}</p>
                                <p class="text-white/70 text-sm">{{ t('blog.author') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Article Content -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <!-- Tags -->
                <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-2 mb-8">
                    <Link
                        v-for="tag in post.tags"
                        :key="tag.id"
                        :href="route('blog.tag', tag.slug)"
                        class="bg-gray-100 hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 text-sm px-4 py-1.5 rounded-full transition-colors"
                    >
                        #{{ tag.name }}
                    </Link>
                </div>

                <!-- Excerpt -->
                <p v-if="post.excerpt" class="text-xl text-gray-600 leading-relaxed mb-8 pb-8 border-b border-gray-200">
                    {{ post.excerpt }}
                </p>

                <!-- Content -->
                <div
                    class="prose prose-lg prose-indigo max-w-none
                           prose-headings:font-bold prose-headings:text-gray-900
                           prose-p:text-gray-700 prose-p:leading-relaxed
                           prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
                           prose-img:rounded-xl prose-img:shadow-lg
                           prose-blockquote:border-l-indigo-600 prose-blockquote:bg-indigo-50 prose-blockquote:py-2
                           prose-code:bg-gray-100 prose-code:rounded prose-code:px-1.5 prose-code:py-0.5
                           prose-pre:bg-gray-900 prose-pre:rounded-xl"
                    v-html="post.content"
                />

                <!-- Share Buttons -->
                <div class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('blog.share') }}</h3>
                    <div class="flex gap-3">
                        <a
                            :href="`https://twitter.com/intent/tweet?url=${encodeURIComponent(post.url)}&text=${encodeURIComponent(post.title)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2 bg-[#1DA1F2] text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                            <span>Twitter</span>
                        </a>
                        <a
                            :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(post.url)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2 bg-[#1877F2] text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>Facebook</span>
                        </a>
                        <a
                            :href="`https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(post.url)}&title=${encodeURIComponent(post.title)}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2 bg-[#0A66C2] text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity"
                        >
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                            <span>LinkedIn</span>
                        </a>
                        <button
                            @click="copyLink"
                            class="flex items-center gap-2 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <span>{{ t('blog.copy_link') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Comments Section -->
                <section v-if="post.allow_comments" class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-900 mb-8">
                        {{ t('blog.comments') }} ({{ post.approved_comments?.length || 0 }})
                    </h3>

                    <!-- Comment Form -->
                    <form @submit.prevent="submitComment" class="bg-gray-50 rounded-2xl p-6 mb-8">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ t('blog.leave_comment') }}</h4>
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('blog.comment_name') }}</label>
                                <input
                                    v-model="commentForm.author_name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('blog.comment_email') }}</label>
                                <input
                                    v-model="commentForm.author_email"
                                    type="email"
                                    required
                                    class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('blog.comment_content') }}</label>
                            <textarea
                                v-model="commentForm.content"
                                rows="4"
                                required
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            ></textarea>
                        </div>
                        <button
                            type="submit"
                            :disabled="commentForm.processing"
                            class="bg-indigo-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
                        >
                            {{ t('blog.submit_comment') }}
                        </button>
                    </form>

                    <!-- Comments List -->
                    <div v-if="post.approved_comments && post.approved_comments.length > 0" class="space-y-6">
                        <div
                            v-for="comment in post.approved_comments"
                            :key="comment.id"
                            class="bg-white border border-gray-200 rounded-xl p-6"
                        >
                            <div class="flex items-start gap-4">
                                <img
                                    :src="comment.avatar_url"
                                    :alt="comment.author_name"
                                    class="w-12 h-12 rounded-full bg-gray-200"
                                >
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <h5 class="font-semibold text-gray-900">{{ comment.author_name }}</h5>
                                        <span class="text-sm text-gray-500">{{ comment.formatted_date }}</span>
                                    </div>
                                    <p class="text-gray-700">{{ comment.content }}</p>

                                    <!-- Replies -->
                                    <div v-if="comment.replies && comment.replies.length > 0" class="mt-4 pl-6 border-l-2 border-indigo-100 space-y-4">
                                        <div
                                            v-for="reply in comment.replies"
                                            :key="reply.id"
                                            class="bg-gray-50 rounded-lg p-4"
                                        >
                                            <div class="flex items-center justify-between mb-2">
                                                <h6 class="font-medium text-gray-900">{{ reply.author_name }}</h6>
                                                <span class="text-xs text-gray-500">{{ reply.formatted_date }}</span>
                                            </div>
                                            <p class="text-gray-700 text-sm">{{ reply.content }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        {{ t('blog.no_comments') }}
                    </div>
                </section>

                <!-- Related Posts -->
                <section v-if="relatedPosts && relatedPosts.length > 0" class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-900 mb-8">{{ t('blog.related') }}</h3>
                    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <Link
                            v-for="related in relatedPosts"
                            :key="related.id"
                            :href="related.url"
                            class="group"
                        >
                            <div class="h-40 rounded-xl overflow-hidden mb-3">
                                <img
                                    v-if="related.featured_image"
                                    :src="related.featured_image"
                                    :alt="related.title"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                >
                                <div v-else class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-600"></div>
                            </div>
                            <h4 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2">
                                {{ related.title }}
                            </h4>
                            <p class="text-sm text-gray-500 mt-1">{{ related.formatted_date }}</p>
                        </Link>
                    </div>
                </section>
            </div>

            <!-- Schema.org JSON-LD -->
            <component :is="'script'" type="application/ld+json" v-html="JSON.stringify(seo.schema)" />
        </article>
    </PublicLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { useTranslation } from '@/composables/useTranslation'

const { t } = useTranslation()

const props = defineProps({
    post: Object,
    relatedPosts: Array,
    seo: Object,
})

const commentForm = useForm({
    author_name: '',
    author_email: '',
    content: '',
    parent_id: null,
})

const submitComment = () => {
    commentForm.post(route('blog.comment.store', props.post.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset()
        },
    })
}

const copyLink = () => {
    navigator.clipboard.writeText(props.post.url)
    alert(t('blog.link_copied'))
}
</script>
