<template>
    <div class="min-h-screen bg-gradient-mesh">
        <!-- Sidebar -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'
            ]"
            class="fixed inset-y-0 left-0 z-50 w-72 bg-gradient-dark transition-all duration-300 ease-in-out lg:translate-x-0 flex flex-col"
        >
            <!-- Logo Section -->
            <div class="flex h-16 items-center justify-between px-4 border-b border-white/10">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white font-bold text-lg shadow-lg">
                        <ArchiveBoxIcon class="h-6 w-6" />
                    </div>
                    <transition name="fade">
                        <span v-if="!sidebarCollapsed" class="text-xl font-bold text-white tracking-tight">
                            {{ $page.props.tenant?.name || 'Boxibox' }}
                        </span>
                    </transition>
                </div>
                <button
                    @click="sidebarOpen = false"
                    class="lg:hidden text-gray-400 hover:text-white transition-colors"
                >
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto sidebar-scroll">
                <!-- Main Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Principal
                    </p>
                    <SidebarLink
                        :href="route('tenant.dashboard')"
                        :active="route().current('tenant.dashboard')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <HomeIcon class="h-5 w-5" />
                        </template>
                        Dashboard
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.sites.index')"
                        :active="route().current('tenant.sites.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <BuildingOffice2Icon class="h-5 w-5" />
                        </template>
                        Sites
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.boxes.index')"
                        :active="route().current('tenant.boxes.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ArchiveBoxIcon class="h-5 w-5" />
                        </template>
                        Boxes
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.plan.index')"
                        :active="route().current('tenant.plan.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <MapIcon class="h-5 w-5" />
                        </template>
                        Plan
                    </SidebarLink>
                </div>

                <!-- Clients Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Clients
                    </p>
                    <SidebarLink
                        :href="route('tenant.prospects.index')"
                        :active="route().current('tenant.prospects.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <UserPlusIcon class="h-5 w-5" />
                        </template>
                        Prospects
                        <template #badge>
                            <span v-if="$page.props.prospectsCount" class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $page.props.prospectsCount }}
                            </span>
                        </template>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.customers.index')"
                        :active="route().current('tenant.customers.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <UsersIcon class="h-5 w-5" />
                        </template>
                        Clients
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.contracts.index')"
                        :active="route().current('tenant.contracts.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <DocumentTextIcon class="h-5 w-5" />
                        </template>
                        Contrats
                    </SidebarLink>
                </div>

                <!-- Finance Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Finance
                    </p>
                    <SidebarLink
                        :href="route('tenant.invoices.index')"
                        :active="route().current('tenant.invoices.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ReceiptPercentIcon class="h-5 w-5" />
                        </template>
                        Factures
                        <template #badge>
                            <span v-if="$page.props.overdueCount" class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $page.props.overdueCount }}
                            </span>
                        </template>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.bulk-invoicing.index')"
                        :active="route().current('tenant.bulk-invoicing.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <DocumentDuplicateIcon class="h-5 w-5" />
                        </template>
                        Facturation groupée
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.sepa-mandates.index')"
                        :active="route().current('tenant.sepa-mandates.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <CreditCardIcon class="h-5 w-5" />
                        </template>
                        Mandats SEPA
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.reminders.index')"
                        :active="route().current('tenant.reminders.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <BellAlertIcon class="h-5 w-5" />
                        </template>
                        Relances
                    </SidebarLink>
                </div>

                <!-- Communication Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Communication
                    </p>
                    <SidebarLink
                        :href="route('tenant.messages.index')"
                        :active="route().current('tenant.messages.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ChatBubbleLeftRightIcon class="h-5 w-5" />
                        </template>
                        Messages
                        <template #badge>
                            <span v-if="$page.props.unreadMessages" class="ml-auto bg-primary-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $page.props.unreadMessages }}
                            </span>
                        </template>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.signatures.index')"
                        :active="route().current('tenant.signatures.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <PencilSquareIcon class="h-5 w-5" />
                        </template>
                        Signatures
                    </SidebarLink>
                </div>
            </nav>

            <!-- Collapse Button (Desktop) -->
            <button
                @click="toggleCollapse"
                class="hidden lg:flex items-center justify-center h-10 mx-3 mb-2 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition-colors"
            >
                <ChevronLeftIcon v-if="!sidebarCollapsed" class="h-5 w-5" />
                <ChevronRightIcon v-else class="h-5 w-5" />
            </button>

            <!-- User Section -->
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white font-semibold shadow-lg">
                        {{ $page.props.auth.user?.name?.charAt(0) || 'U' }}
                    </div>
                    <transition name="fade">
                        <div v-if="!sidebarCollapsed" class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">
                                {{ $page.props.auth.user?.name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $page.props.auth.user?.email }}
                            </p>
                        </div>
                    </transition>
                    <div class="relative" v-if="!sidebarCollapsed">
                        <button
                            @click="showUserMenu = !showUserMenu"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-colors"
                        >
                            <EllipsisVerticalIcon class="h-5 w-5" />
                        </button>
                        <transition name="dropdown">
                            <div v-if="showUserMenu" class="absolute bottom-full right-0 mb-2 w-48 rounded-xl bg-white shadow-xl border border-gray-100 py-1 z-50">
                                <Link
                                    :href="route('tenant.settings')"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <Cog6ToothIcon class="h-4 w-4 mr-3 text-gray-400" />
                                    Paramètres
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                                >
                                    <ArrowRightOnRectangleIcon class="h-4 w-4 mr-3" />
                                    Déconnexion
                                </Link>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <transition name="fade">
            <div
                v-if="sidebarOpen"
                @click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden"
            ></div>
        </transition>

        <!-- Main Content -->
        <div :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'" class="transition-all duration-300">
            <!-- Top Header -->
            <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-200/50">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <!-- Mobile Menu Button -->
                    <button
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                    >
                        <Bars3Icon class="h-6 w-6" />
                    </button>

                    <!-- Page Title & Breadcrumb -->
                    <div class="flex-1 flex items-center">
                        <div>
                            <h1 v-if="title" class="text-xl font-bold text-gray-900">
                                {{ title }}
                            </h1>
                            <nav v-if="breadcrumbs && breadcrumbs.length" class="flex items-center space-x-2 text-sm text-gray-500 mt-0.5">
                                <Link :href="route('tenant.dashboard')" class="hover:text-primary-600 transition-colors">
                                    Dashboard
                                </Link>
                                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                                    <ChevronRightIcon class="h-4 w-4 text-gray-300" />
                                    <span v-if="index === breadcrumbs.length - 1" class="text-gray-700 font-medium">
                                        {{ crumb.label }}
                                    </span>
                                    <Link v-else :href="crumb.href" class="hover:text-primary-600 transition-colors">
                                        {{ crumb.label }}
                                    </Link>
                                </template>
                            </nav>
                        </div>
                    </div>

                    <!-- Right Section -->
                    <div class="flex items-center space-x-3">
                        <!-- Search Button -->
                        <button
                            @click="showSearch = true"
                            class="hidden sm:flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm text-gray-500 transition-colors"
                        >
                            <MagnifyingGlassIcon class="h-4 w-4 mr-2" />
                            <span>Rechercher...</span>
                            <kbd class="ml-3 px-2 py-0.5 bg-white rounded text-xs text-gray-400 border border-gray-200">
                                /
                            </kbd>
                        </button>

                        <!-- Quick Actions -->
                        <div class="relative">
                            <button
                                @click="showQuickActions = !showQuickActions"
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                <PlusIcon class="h-5 w-5" />
                            </button>
                            <transition name="dropdown">
                                <div v-if="showQuickActions" class="absolute right-0 mt-2 w-56 rounded-xl bg-white shadow-xl border border-gray-100 py-2 z-50">
                                    <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase">Actions rapides</p>
                                    <Link :href="route('tenant.customers.create')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <UserPlusIcon class="h-4 w-4 mr-3 text-primary-500" />
                                        Nouveau client
                                    </Link>
                                    <Link :href="route('tenant.contracts.create')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <DocumentPlusIcon class="h-4 w-4 mr-3 text-emerald-500" />
                                        Nouveau contrat
                                    </Link>
                                    <Link :href="route('tenant.invoices.create')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <ReceiptPercentIcon class="h-4 w-4 mr-3 text-purple-500" />
                                        Nouvelle facture
                                    </Link>
                                </div>
                            </transition>
                        </div>

                        <!-- Notifications -->
                        <div class="relative">
                            <button
                                @click="showNotifications = !showNotifications"
                                class="relative p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                            >
                                <BellIcon class="h-5 w-5" />
                                <span
                                    v-if="$page.props.notificationsCount > 0"
                                    class="absolute top-0.5 right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white ring-2 ring-white"
                                >
                                    {{ $page.props.notificationsCount > 9 ? '9+' : $page.props.notificationsCount }}
                                </span>
                            </button>
                            <transition name="dropdown">
                                <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 rounded-xl bg-white shadow-xl border border-gray-100 z-50">
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        <div class="px-4 py-8 text-center text-gray-500 text-sm">
                                            Aucune nouvelle notification
                                        </div>
                                    </div>
                                    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                                        <a href="#" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                            Voir toutes les notifications
                                        </a>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <!-- Flash Messages -->
                <transition name="slide-down">
                    <div v-if="$page.props.flash?.success" class="mb-6 flex items-center p-4 rounded-xl bg-emerald-50 border border-emerald-200 animate-fade-in-down">
                        <CheckCircleIcon class="h-5 w-5 text-emerald-500 mr-3 flex-shrink-0" />
                        <p class="text-sm text-emerald-800">{{ $page.props.flash.success }}</p>
                        <button @click="dismissFlash('success')" class="ml-auto p-1 hover:bg-emerald-100 rounded-lg transition-colors">
                            <XMarkIcon class="h-4 w-4 text-emerald-500" />
                        </button>
                    </div>
                </transition>
                <transition name="slide-down">
                    <div v-if="$page.props.flash?.error" class="mb-6 flex items-center p-4 rounded-xl bg-red-50 border border-red-200 animate-fade-in-down">
                        <ExclamationCircleIcon class="h-5 w-5 text-red-500 mr-3 flex-shrink-0" />
                        <p class="text-sm text-red-800">{{ $page.props.flash.error }}</p>
                        <button @click="dismissFlash('error')" class="ml-auto p-1 hover:bg-red-100 rounded-lg transition-colors">
                            <XMarkIcon class="h-4 w-4 text-red-500" />
                        </button>
                    </div>
                </transition>
                <transition name="slide-down">
                    <div v-if="$page.props.flash?.warning" class="mb-6 flex items-center p-4 rounded-xl bg-amber-50 border border-amber-200 animate-fade-in-down">
                        <ExclamationTriangleIcon class="h-5 w-5 text-amber-500 mr-3 flex-shrink-0" />
                        <p class="text-sm text-amber-800">{{ $page.props.flash.warning }}</p>
                        <button @click="dismissFlash('warning')" class="ml-auto p-1 hover:bg-amber-100 rounded-lg transition-colors">
                            <XMarkIcon class="h-4 w-4 text-amber-500" />
                        </button>
                    </div>
                </transition>
                <transition name="slide-down">
                    <div v-if="$page.props.flash?.info" class="mb-6 flex items-center p-4 rounded-xl bg-blue-50 border border-blue-200 animate-fade-in-down">
                        <InformationCircleIcon class="h-5 w-5 text-blue-500 mr-3 flex-shrink-0" />
                        <p class="text-sm text-blue-800">{{ $page.props.flash.info }}</p>
                        <button @click="dismissFlash('info')" class="ml-auto p-1 hover:bg-blue-100 rounded-lg transition-colors">
                            <XMarkIcon class="h-4 w-4 text-blue-500" />
                        </button>
                    </div>
                </transition>

                <slot />
            </main>

            <!-- Footer -->
            <footer class="border-t border-gray-200 bg-white/50 py-4 px-8">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <p>&copy; {{ new Date().getFullYear() }} Boxibox. Tous droits réservés.</p>
                    <p>Version 1.0.0</p>
                </div>
            </footer>
        </div>

        <!-- Search Modal -->
        <transition name="modal">
            <div v-if="showSearch" class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showSearch = false"></div>
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl">
                    <div class="flex items-center px-4 border-b border-gray-100">
                        <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                        <input
                            type="text"
                            placeholder="Rechercher clients, contrats, factures..."
                            class="w-full px-4 py-4 text-gray-900 placeholder-gray-400 focus:outline-none"
                            autofocus
                        />
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-400 border border-gray-200">
                            ESC
                        </kbd>
                    </div>
                    <div class="p-4 text-center text-gray-500 text-sm">
                        Commencez à taper pour rechercher...
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import SidebarLink from '@/Components/SidebarLink.vue'
import {
    HomeIcon,
    BuildingOffice2Icon,
    ArchiveBoxIcon,
    UsersIcon,
    UserPlusIcon,
    DocumentTextIcon,
    ReceiptPercentIcon,
    DocumentDuplicateIcon,
    CreditCardIcon,
    BellAlertIcon,
    ChatBubbleLeftRightIcon,
    PencilSquareIcon,
    Cog6ToothIcon,
    BellIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    Bars3Icon,
    XMarkIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    EllipsisVerticalIcon,
    ArrowRightOnRectangleIcon,
    DocumentPlusIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MapIcon,
} from '@heroicons/vue/24/outline'

defineProps({
    title: String,
    breadcrumbs: Array,
})

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const showUserMenu = ref(false)
const showNotifications = ref(false)
const showQuickActions = ref(false)
const showSearch = ref(false)

const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value)
}

const dismissFlash = (type) => {
    // Flash dismissal logic
}

// Keyboard shortcuts
const handleKeydown = (e) => {
    if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
        e.preventDefault()
        showSearch.value = true
    }
    if (e.key === 'Escape') {
        showSearch.value = false
        showNotifications.value = false
        showQuickActions.value = false
        showUserMenu.value = false
    }
}

onMounted(() => {
    // Restore sidebar state
    const savedState = localStorage.getItem('sidebarCollapsed')
    if (savedState !== null) {
        sidebarCollapsed.value = savedState === 'true'
    }

    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
    transform: scale(0.95);
}
</style>
