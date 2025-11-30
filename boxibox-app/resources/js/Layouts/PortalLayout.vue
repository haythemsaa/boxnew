<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top Navigation Bar -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <Link :href="route('portal.dashboard')" class="flex items-center space-x-3">
                            <img
                                v-if="$page.props.tenant?.logo"
                                :src="$page.props.tenant.logo"
                                :alt="$page.props.tenant.name"
                                class="h-8 w-8 rounded"
                            />
                            <div v-else class="flex h-8 w-8 items-center justify-center rounded bg-primary-600 text-white font-bold">
                                {{ $page.props.tenant?.name?.charAt(0) || 'B' }}
                            </div>
                            <span class="text-xl font-semibold text-gray-900">
                                {{ $page.props.tenant?.name || 'Boxibox' }}
                            </span>
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden sm:flex sm:items-center sm:space-x-8">
                        <NavLink
                            :href="route('portal.dashboard')"
                            :active="route().current('portal.dashboard')"
                        >
                            Dashboard
                        </NavLink>

                        <NavLink
                            :href="route('portal.boxes.index')"
                            :active="route().current('portal.boxes.*')"
                        >
                            My Boxes
                        </NavLink>

                        <NavLink
                            :href="route('portal.invoices.index')"
                            :active="route().current('portal.invoices.*')"
                        >
                            Invoices
                        </NavLink>

                        <NavLink
                            :href="route('portal.services.index')"
                            :active="route().current('portal.services.*')"
                        >
                            Services
                        </NavLink>

                        <NavLink
                            :href="route('portal.messages.index')"
                            :active="route().current('portal.messages.*')"
                        >
                            Messages
                            <span v-if="$page.props.messagesCount > 0" class="ml-1.5 rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">
                                {{ $page.props.messagesCount }}
                            </span>
                        </NavLink>

                        <!-- User Menu -->
                        <div class="relative ml-3">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center space-x-2 rounded-full bg-white p-2 text-gray-400 hover:text-gray-500"
                            >
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-white font-semibold">
                                    {{ $page.props.auth.user?.name?.charAt(0) || 'U' }}
                                </div>
                            </button>

                            <div
                                v-if="showUserMenu"
                                @click.away="showUserMenu = false"
                                class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5"
                            >
                                <Link
                                    :href="route('portal.profile')"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    My Profile
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                >
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="flex items-center sm:hidden">
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    :d="mobileMenuOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="mobileMenuOpen" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <Link
                        :href="route('portal.dashboard')"
                        class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
                        :class="route().current('portal.dashboard') ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700'"
                    >
                        Dashboard
                    </Link>
                    <Link
                        :href="route('portal.boxes.index')"
                        class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
                        :class="route().current('portal.boxes.*') ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700'"
                    >
                        My Boxes
                    </Link>
                    <Link
                        :href="route('portal.invoices.index')"
                        class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
                        :class="route().current('portal.invoices.*') ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700'"
                    >
                        Invoices
                    </Link>
                    <Link
                        :href="route('portal.services.index')"
                        class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
                        :class="route().current('portal.services.*') ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700'"
                    >
                        Services
                    </Link>
                    <Link
                        :href="route('portal.messages.index')"
                        class="block border-l-4 py-2 pl-3 pr-4 text-base font-medium"
                        :class="route().current('portal.messages.*') ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700'"
                    >
                        Messages
                    </Link>
                </div>
                <div class="border-t border-gray-200 pb-3 pt-4">
                    <div class="flex items-center px-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-600 text-white font-semibold">
                            {{ $page.props.auth.user?.name?.charAt(0) || 'U' }}
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user?.name }}</div>
                            <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user?.email }}</div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <Link
                            :href="route('portal.profile')"
                            class="block px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                        >
                            Profile
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="block w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                        >
                            Logout
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="mx-auto max-w-7xl py-6 px-4 sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                {{ $page.props.flash.error }}
            </div>
            <div v-if="$page.props.flash?.warning" class="mb-4 rounded-lg bg-yellow-50 p-4 text-yellow-800">
                {{ $page.props.flash.warning }}
            </div>
            <div v-if="$page.props.flash?.info" class="mb-4 rounded-lg bg-blue-50 p-4 text-blue-800">
                {{ $page.props.flash.info }}
            </div>

            <!-- Page Title -->
            <div v-if="title" class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">{{ title }}</h1>
            </div>

            <slot />
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import NavLink from '@/Components/NavLink.vue'

defineProps({
    title: String,
})

const mobileMenuOpen = ref(false)
const showUserMenu = ref(false)
</script>
