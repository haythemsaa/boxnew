<template>
    <nav v-if="links && links.length > 3" class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Précédent
            </Link>
            <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                Précédent
            </span>
            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Suivant
            </Link>
            <span v-else class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                Suivant
            </span>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
            <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px" aria-label="Pagination">
                <template v-for="(link, index) in links" :key="index">
                    <!-- Previous Button -->
                    <Link
                        v-if="index === 0 && link.url"
                        :href="link.url"
                        class="relative inline-flex items-center px-3 py-2 rounded-l-xl border border-gray-200 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                        <ChevronLeftIcon class="h-4 w-4" />
                    </Link>
                    <span
                        v-else-if="index === 0"
                        class="relative inline-flex items-center px-3 py-2 rounded-l-xl border border-gray-200 bg-gray-50 text-sm font-medium text-gray-300 cursor-not-allowed"
                    >
                        <ChevronLeftIcon class="h-4 w-4" />
                    </span>

                    <!-- Page Numbers -->
                    <template v-else-if="index !== links.length - 1">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                link.active
                                    ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                                    : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            :class="[
                                link.active
                                    ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
                                    : 'bg-white border-gray-200 text-gray-300',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                            ]"
                            v-html="link.label"
                        />
                    </template>

                    <!-- Next Button -->
                    <Link
                        v-else-if="link.url"
                        :href="link.url"
                        class="relative inline-flex items-center px-3 py-2 rounded-r-xl border border-gray-200 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                        <ChevronRightIcon class="h-4 w-4" />
                    </Link>
                    <span
                        v-else
                        class="relative inline-flex items-center px-3 py-2 rounded-r-xl border border-gray-200 bg-gray-50 text-sm font-medium text-gray-300 cursor-not-allowed"
                    >
                        <ChevronRightIcon class="h-4 w-4" />
                    </span>
                </template>
            </nav>
        </div>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({
    links: Array,
})
</script>
