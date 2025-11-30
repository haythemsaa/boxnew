<template>
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 pb-24">
        <!-- Status Bar Spacer (for PWA) -->
        <div class="h-safe-area-inset-top bg-primary-600"></div>

        <!-- Top Header -->
        <header class="bg-gradient-to-r from-primary-600 via-primary-600 to-primary-700 text-white shadow-xl fixed top-0 left-0 right-0 z-50 pt-safe-area-inset-top">
            <div class="px-4 py-4">
                <div class="flex items-center justify-between">
                    <!-- Back Button or Logo -->
                    <div class="flex items-center">
                        <button
                            v-if="showBack"
                            @click="goBack"
                            class="mr-3 p-2 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 transition-all duration-200"
                        >
                            <ArrowLeftIcon class="w-5 h-5" />
                        </button>
                        <div v-else class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-lg shadow-primary-700/30">
                                <svg class="w-6 h-6 text-primary-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 7h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zM10 4h4v3h-4V4zm10 16H4V9h16v11z"/>
                                    <path d="M11 12h2v2h-2zM7 12h2v2H7zM15 12h2v2h-2z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold text-xl tracking-tight">Boxibox</span>
                                <span class="text-xs text-primary-200 block -mt-1">Self-Storage</span>
                            </div>
                        </div>
                    </div>

                    <!-- Title (centered) -->
                    <h1 v-if="title && showBack" class="text-lg font-semibold absolute left-1/2 transform -translate-x-1/2 truncate max-w-[50%]">
                        {{ title }}
                    </h1>

                    <!-- Right Actions -->
                    <div class="flex items-center space-x-2">
                        <button
                            @click="showNotifications = true"
                            class="relative p-2.5 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 transition-all duration-200"
                        >
                            <BellIcon class="w-5 h-5" />
                            <span
                                v-if="notificationCount > 0"
                                class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-br from-red-500 to-rose-600 rounded-full text-xs flex items-center justify-center font-bold shadow-lg animate-pulse"
                            >
                                {{ notificationCount > 9 ? '9+' : notificationCount }}
                            </span>
                        </button>
                        <button
                            @click="showProfile = true"
                            class="w-10 h-10 bg-gradient-to-br from-white/30 to-white/10 rounded-xl flex items-center justify-center hover:from-white/40 hover:to-white/20 active:scale-95 transition-all duration-200 ring-2 ring-white/20"
                        >
                            <span class="font-bold text-sm">{{ userInitial }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="pt-20 px-4 py-5">
            <!-- Flash Messages -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="$page.props.flash?.success" class="mb-4 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-600 p-4 text-white flex items-center shadow-lg shadow-green-500/30">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mr-3">
                        <CheckCircleIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="font-semibold">Succes</p>
                        <p class="text-sm text-green-100">{{ $page.props.flash.success }}</p>
                    </div>
                </div>
            </Transition>

            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="$page.props.flash?.error" class="mb-4 rounded-2xl bg-gradient-to-r from-red-500 to-rose-600 p-4 text-white flex items-center shadow-lg shadow-red-500/30">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mr-3">
                        <ExclamationCircleIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="font-semibold">Erreur</p>
                        <p class="text-sm text-red-100">{{ $page.props.flash.error }}</p>
                    </div>
                </div>
            </Transition>

            <slot />
        </main>

        <!-- Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-xl border-t border-gray-100 shadow-2xl shadow-gray-900/10 z-50">
            <div class="flex justify-around py-2 px-2">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex flex-col items-center py-2 px-4 rounded-2xl transition-all duration-300 relative group"
                    :class="isActive(item.route)
                        ? 'text-primary-600'
                        : 'text-gray-400 hover:text-gray-600'"
                >
                    <!-- Active indicator -->
                    <div
                        v-if="isActive(item.route)"
                        class="absolute -top-1 left-1/2 -translate-x-1/2 w-8 h-1 bg-gradient-to-r from-primary-500 to-primary-600 rounded-full"
                    ></div>

                    <!-- Icon container -->
                    <div
                        class="p-2 rounded-xl transition-all duration-300"
                        :class="isActive(item.route)
                            ? 'bg-primary-50'
                            : 'group-hover:bg-gray-50'"
                    >
                        <component
                            :is="item.icon"
                            class="w-6 h-6 transition-transform duration-300"
                            :class="isActive(item.route) ? 'scale-110' : 'group-hover:scale-105'"
                        />
                    </div>
                    <span class="text-xs mt-1 font-semibold">{{ item.label }}</span>
                </Link>
            </div>
            <!-- Safe area for iOS -->
            <div class="h-safe-area-inset-bottom bg-white"></div>
        </nav>

        <!-- Notifications Slide Over -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showNotifications" class="fixed inset-0 z-[100]">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showNotifications = false"></div>
                    <Transition
                        enter-active-class="transform transition-transform duration-300 ease-out"
                        enter-from-class="translate-x-full"
                        enter-to-class="translate-x-0"
                        leave-active-class="transform transition-transform duration-300 ease-in"
                        leave-from-class="translate-x-0"
                        leave-to-class="translate-x-full"
                    >
                        <div v-if="showNotifications" class="absolute right-0 top-0 bottom-0 w-[85%] max-w-sm bg-white shadow-2xl">
                            <!-- Header -->
                            <div class="bg-gradient-to-r from-primary-600 to-primary-700 p-5 pt-safe-area-inset-top">
                                <div class="flex items-center justify-between text-white">
                                    <div>
                                        <h2 class="text-xl font-bold">Notifications</h2>
                                        <p class="text-sm text-primary-200">{{ notificationCount }} non lue(s)</p>
                                    </div>
                                    <button
                                        @click="showNotifications = false"
                                        class="p-2 rounded-xl bg-white/10 hover:bg-white/20 transition"
                                    >
                                        <XMarkIcon class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-4 overflow-y-auto" style="max-height: calc(100vh - 120px);">
                                <div v-if="notifications.length === 0" class="text-center py-16">
                                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <BellIcon class="w-10 h-10 text-gray-300" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Tout est lu !</h3>
                                    <p class="text-gray-500">Aucune nouvelle notification</p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="notification in notifications"
                                        :key="notification.id"
                                        class="p-4 rounded-2xl transition-all duration-200 hover:shadow-md"
                                        :class="notification.read
                                            ? 'bg-gray-50'
                                            : 'bg-gradient-to-r from-primary-50 to-blue-50 border-l-4 border-primary-500'"
                                    >
                                        <div class="flex items-start">
                                            <div
                                                class="w-10 h-10 rounded-xl flex items-center justify-center mr-3 flex-shrink-0"
                                                :class="notification.read ? 'bg-gray-200' : 'bg-primary-100'"
                                            >
                                                <component
                                                    :is="getNotificationIcon(notification.type)"
                                                    class="w-5 h-5"
                                                    :class="notification.read ? 'text-gray-500' : 'text-primary-600'"
                                                />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900">{{ notification.title }}</p>
                                                <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
                                                <p class="text-xs text-gray-400 mt-2 flex items-center">
                                                    <ClockIcon class="w-3 h-3 mr-1" />
                                                    {{ notification.time }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Profile Sheet -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showProfile" class="fixed inset-0 z-[100]">
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showProfile = false"></div>
                    <Transition
                        enter-active-class="transform transition-transform duration-300 ease-out"
                        enter-from-class="translate-y-full"
                        enter-to-class="translate-y-0"
                        leave-active-class="transform transition-transform duration-300 ease-in"
                        leave-from-class="translate-y-0"
                        leave-to-class="translate-y-full"
                    >
                        <div v-if="showProfile" class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[2rem] shadow-2xl">
                            <!-- Handle -->
                            <div class="flex justify-center pt-3 pb-2">
                                <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                            </div>

                            <!-- Profile Header -->
                            <div class="px-6 pb-4">
                                <div class="flex items-center">
                                    <div class="relative">
                                        <div class="w-18 h-18 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                                            <span class="text-white text-3xl font-bold">{{ userInitial }}</span>
                                        </div>
                                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-4 border-white"></div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-xl font-bold text-gray-900">{{ $page.props.auth.user?.name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $page.props.auth.user?.email }}</p>
                                        <span class="inline-flex items-center mt-2 px-3 py-1 bg-primary-50 text-primary-700 text-xs font-semibold rounded-full">
                                            <SparklesIcon class="w-3 h-3 mr-1" />
                                            Client Premium
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Menu Items -->
                            <div class="px-4 pb-4">
                                <div class="bg-gray-50 rounded-2xl p-2 space-y-1">
                                    <Link
                                        v-for="item in profileMenuItems"
                                        :key="item.route"
                                        :href="route(item.route)"
                                        class="flex items-center p-3 rounded-xl hover:bg-white active:scale-[0.98] transition-all duration-200"
                                        @click="showProfile = false"
                                    >
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mr-3" :class="item.iconBg">
                                            <component :is="item.icon" class="w-5 h-5" :class="item.iconColor" />
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-900">{{ item.label }}</span>
                                            <p class="text-xs text-gray-500">{{ item.description }}</p>
                                        </div>
                                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                                    </Link>
                                </div>

                                <!-- Logout Button -->
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex items-center w-full mt-4 p-4 rounded-2xl bg-gradient-to-r from-red-50 to-rose-50 hover:from-red-100 hover:to-rose-100 active:scale-[0.98] transition-all duration-200"
                                >
                                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center mr-3">
                                        <ArrowRightOnRectangleIcon class="w-5 h-5 text-red-600" />
                                    </div>
                                    <span class="font-semibold text-red-600">Deconnexion</span>
                                </Link>
                            </div>

                            <!-- Safe area for iOS -->
                            <div class="h-safe-area-inset-bottom bg-white"></div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Loading Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isLoading" class="fixed inset-0 z-[200] bg-white/80 backdrop-blur-sm flex items-center justify-center">
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-primary-200 rounded-full"></div>
                        <div class="w-16 h-16 border-4 border-primary-600 rounded-full border-t-transparent animate-spin absolute top-0 left-0"></div>
                    </div>
                    <p class="mt-4 text-gray-600 font-medium">Chargement...</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    HomeIcon,
    CubeIcon,
    DocumentTextIcon,
    CreditCardIcon,
    Bars3Icon,
    BellIcon,
    ArrowLeftIcon,
    XMarkIcon,
    UserCircleIcon,
    Cog6ToothIcon,
    QuestionMarkCircleIcon,
    ArrowRightOnRectangleIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ClockIcon,
    SparklesIcon,
    DocumentDuplicateIcon,
    ShieldCheckIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'
import {
    HomeIcon as HomeIconSolid,
    CubeIcon as CubeIconSolid,
    DocumentTextIcon as DocumentTextIconSolid,
    CreditCardIcon as CreditCardIconSolid,
    Bars3Icon as Bars3IconSolid,
} from '@heroicons/vue/24/solid'

const props = defineProps({
    title: String,
    showBack: {
        type: Boolean,
        default: false,
    },
})

const page = usePage()
const showNotifications = ref(false)
const showProfile = ref(false)
const isLoading = ref(false)

// Navigation items
const navItems = [
    { route: 'mobile.dashboard', icon: HomeIcon, label: 'Accueil' },
    { route: 'mobile.boxes', icon: CubeIcon, label: 'Mes Box' },
    { route: 'mobile.invoices', icon: DocumentTextIcon, label: 'Factures' },
    { route: 'mobile.payments', icon: CreditCardIcon, label: 'Paiements' },
    { route: 'mobile.more', icon: Bars3Icon, label: 'Plus' },
]

// Profile menu items
const profileMenuItems = [
    {
        route: 'mobile.profile',
        icon: UserCircleIcon,
        label: 'Mon profil',
        description: 'Informations personnelles',
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600'
    },
    {
        route: 'mobile.documents',
        icon: DocumentDuplicateIcon,
        label: 'Mes documents',
        description: 'Contrats, factures, etc.',
        iconBg: 'bg-purple-100',
        iconColor: 'text-purple-600'
    },
    {
        route: 'mobile.settings',
        icon: Cog6ToothIcon,
        label: 'Parametres',
        description: 'Notifications, securite',
        iconBg: 'bg-gray-200',
        iconColor: 'text-gray-600'
    },
    {
        route: 'mobile.support',
        icon: ChatBubbleLeftRightIcon,
        label: 'Support',
        description: 'Aide et contact',
        iconBg: 'bg-green-100',
        iconColor: 'text-green-600'
    },
]

const notifications = ref([
    { id: 1, type: 'invoice', title: 'Nouvelle facture', message: 'Votre facture de decembre est disponible', time: 'Il y a 2h', read: false },
    { id: 2, type: 'payment', title: 'Paiement confirme', message: 'Votre paiement de 150€ a ete accepte', time: 'Hier', read: true },
    { id: 3, type: 'alert', title: 'Rappel', message: 'Votre contrat expire dans 30 jours', time: 'Il y a 3 jours', read: true },
])

const notificationCount = computed(() => notifications.value.filter(n => !n.read).length)

const userInitial = computed(() => {
    return page.props.auth?.user?.name?.charAt(0)?.toUpperCase() || 'U'
})

const isActive = (routeName) => {
    return route().current(routeName) || route().current(routeName + '.*')
}

const goBack = () => {
    window.history.back()
}

const getNotificationIcon = (type) => {
    const icons = {
        invoice: DocumentTextIcon,
        payment: CreditCardIcon,
        alert: BellIcon,
        contract: DocumentDuplicateIcon,
    }
    return icons[type] || BellIcon
}

// Handle loading state
let removeStartListener, removeFinishListener

onMounted(() => {
    removeStartListener = router.on('start', () => {
        isLoading.value = true
    })
    removeFinishListener = router.on('finish', () => {
        isLoading.value = false
    })
})

onUnmounted(() => {
    removeStartListener?.()
    removeFinishListener?.()
})
</script>

<style scoped>
.h-safe-area-inset-top {
    height: env(safe-area-inset-top, 0px);
}

.pt-safe-area-inset-top {
    padding-top: env(safe-area-inset-top, 0px);
}

.h-safe-area-inset-bottom {
    height: env(safe-area-inset-bottom, 0px);
}

.w-18 {
    width: 4.5rem;
}

.h-18 {
    height: 4.5rem;
}

/* Smooth scrolling */
:deep(*) {
    -webkit-overflow-scrolling: touch;
}

/* Prevent text selection on interactive elements */
button, a {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    user-select: none;
}
</style>
