<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div>
            <Link href="/">
                <div class="flex items-center space-x-2">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-600 text-white text-xl font-bold">
                        B
                    </div>
                    <span class="text-2xl font-bold text-gray-900">Boxibox</span>
                </div>
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <slot />
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-600">
            <p>&copy; {{ new Date().getFullYear() }} Boxibox. All rights reserved.</p>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>
