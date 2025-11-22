<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transition-transform duration-300 ease-in-out lg:translate-x-0"
        >
            <!-- Logo -->
            <div class="flex h-16 items-center justify-between border-b px-6">
                <div class="flex items-center space-x-3">
                    <img
                        v-if="$page.props.tenant?.logo"
                        :src="$page.props.tenant.logo"
                        :alt="$page.props.tenant.name"
                        class="h-8 w-8 rounded"
                    />
                    <div v-else class="flex h-8 w-8 items-center justify-center rounded bg-primary-600 text-white font-bold">
                        {{ $page.props.tenant?.name?.charAt(0) || 'B' }}
                    </div>
                    <span class="text-lg font-semibold text-gray-900">
                        {{ $page.props.tenant?.name || 'Boxibox' }}
                    </span>
                </div>
                <button
                    @click="sidebarOpen = false"
                    class="lg:hidden text-gray-500 hover:text-gray-700"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 px-3 py-4">
                <NavLink
                    :href="route('tenant.dashboard')"
                    :active="route().current('tenant.dashboard')"
                    icon="home"
                >
                    Dashboard
                </NavLink>

                <NavLink
                    :href="route('tenant.sites.index')"
                    :active="route().current('tenant.sites.*')"
                    icon="map"
                >
                    Sites
                </NavLink>

                <NavLink
                    :href="route('tenant.boxes.index')"
                    :active="route().current('tenant.boxes.*')"
                    icon="box"
                >
                    Boxes
                </NavLink>

                <NavLink
                    :href="route('tenant.customers.index')"
                    :active="route().current('tenant.customers.*')"
                    icon="users"
                >
                    Customers
                </NavLink>

                <NavLink
                    :href="route('tenant.contracts.index')"
                    :active="route().current('tenant.contracts.*')"
                    icon="file-text"
                >
                    Contracts
                </NavLink>

                <NavLink
                    :href="route('tenant.invoices.index')"
                    :active="route().current('tenant.invoices.*')"
                    icon="receipt"
                >
                    Invoices
                </NavLink>

                <NavLink
                    :href="route('tenant.messages.index')"
                    :active="route().current('tenant.messages.*')"
                    icon="message"
                    :badge="$page.props.messagesCount"
                >
                    Messages
                </NavLink>

                <NavLink
                    :href="route('tenant.settings')"
                    :active="route().current('tenant.settings')"
                    icon="settings"
                >
                    Settings
                </NavLink>
            </nav>

            <!-- User Menu -->
            <div class="border-t p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-600 text-white font-semibold">
                        {{ $page.props.auth.user?.name?.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $page.props.auth.user?.name }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">
                            {{ $page.props.auth.user?.email }}
                        </p>
                    </div>
                </div>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="mt-3 w-full rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                >
                    Logout
                </Link>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 lg:hidden"
        ></div>

        <!-- Main Content -->
        <div class="lg:pl-64">
            <!-- Header -->
            <header class="sticky top-0 z-30 bg-white shadow">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <button
                        @click="sidebarOpen = true"
                        class="lg:hidden text-gray-500 hover:text-gray-700"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="flex-1">
                        <h1 v-if="title" class="text-xl font-semibold text-gray-900">
                            {{ title }}
                        </h1>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button
                            class="relative text-gray-500 hover:text-gray-700"
                            @click="showNotifications = !showNotifications"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                v-if="$page.props.notificationsCount > 0"
                                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-xs font-bold text-white"
                            >
                                {{ $page.props.notificationsCount }}
                            </span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
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

                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import NavLink from '@/Components/NavLink.vue'

defineProps({
    title: String,
})

const sidebarOpen = ref(false)
const showNotifications = ref(false)
</script>
