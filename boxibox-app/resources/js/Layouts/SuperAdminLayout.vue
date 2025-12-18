<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    HomeIcon,
    BuildingOffice2Icon,
    UsersIcon,
    CreditCardIcon,
    ChartBarIcon,
    Cog6ToothIcon,
    ArrowLeftOnRectangleIcon,
    Bars3Icon,
    XMarkIcon,
    BellIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    UserCircleIcon,
    ArrowPathIcon,
    DocumentTextIcon,
    EnvelopeIcon,
    MegaphoneIcon,
    CloudArrowUpIcon,
    FlagIcon,
    BanknotesIcon,
    PuzzlePieceIcon,
    ChatBubbleLeftRightIcon,
    ServerIcon,
    ShieldCheckIcon,
    GlobeAltIcon,
    CpuChipIcon,
    ChartPieIcon,
    WrenchScrewdriverIcon,
    BeakerIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    title: {
        type: String,
        default: 'SuperAdmin'
    }
})

const page = usePage()
const user = computed(() => page.props.auth?.user)
const isImpersonating = computed(() => page.props.auth?.impersonating)

const sidebarOpen = ref(false)
const userMenuOpen = ref(false)

// Navigation organisée par sections - only include routes that exist
const navigationSections = [
    {
        title: 'Principal',
        items: [
            { name: 'Dashboard', href: route('superadmin.dashboard'), icon: HomeIcon, current: route().current('superadmin.dashboard') },
            { name: 'Tenants', href: route('superadmin.tenants.index'), icon: BuildingOffice2Icon, current: route().current('superadmin.tenants.*'), badge: page.props.tenantsCount },
            { name: 'Utilisateurs', href: route('superadmin.users.index'), icon: UsersIcon, current: route().current('superadmin.users.*') },
        ]
    },
    {
        title: 'Support & Communication',
        items: [
            { name: 'Support Technique', href: route('superadmin.support.index'), icon: ChatBubbleLeftRightIcon, current: route().current('superadmin.support.*'), badge: page.props.openTicketsCount, badgeColor: 'red' },
            { name: 'Annonces', href: route('superadmin.announcements.index'), icon: MegaphoneIcon, current: route().current('superadmin.announcements.*') },
            { name: 'Templates Email', href: route('superadmin.email-templates.index'), icon: EnvelopeIcon, current: route().current('superadmin.email-templates.*') },
        ]
    },
    {
        title: 'Facturation',
        items: [
            { name: 'Abonnements', href: route('superadmin.subscriptions.index'), icon: CreditCardIcon, current: route().current('superadmin.subscriptions.*') },
            { name: 'Facturation', href: route('superadmin.billing.index'), icon: BanknotesIcon, current: route().current('superadmin.billing.*') },
            { name: 'Plans Tarifaires', href: route('superadmin.plans.index'), icon: DocumentTextIcon, current: route().current('superadmin.plans.*') },
            { name: 'Credits Email/SMS', href: route('superadmin.credits.index'), icon: EnvelopeIcon, current: route().current('superadmin.credits.*') },
            { name: 'Modules', href: route('superadmin.modules.index'), icon: PuzzlePieceIcon, current: route().current('superadmin.modules.*') },
        ]
    },
    {
        title: 'Analytics',
        items: [
            { name: 'Analytics Global', href: route('superadmin.analytics.index'), icon: ChartBarIcon, current: route().current('superadmin.analytics.*') },
        ]
    },
    {
        title: 'Configuration',
        items: [
            { name: 'Feature Flags', href: route('superadmin.feature-flags.index'), icon: FlagIcon, current: route().current('superadmin.feature-flags.*') },
            { name: 'Parametres', href: route('superadmin.settings.index'), icon: Cog6ToothIcon, current: route().current('superadmin.settings.*') },
        ]
    },
    {
        title: 'Systeme',
        items: [
            { name: 'Logs Activite', href: route('superadmin.activity-logs.index'), icon: DocumentTextIcon, current: route().current('superadmin.activity-logs.*') },
            { name: 'Backups', href: route('superadmin.backups.index'), icon: CloudArrowUpIcon, current: route().current('superadmin.backups.*') },
        ]
    },
]

// Ancienne navigation plate pour compatibilité mobile
const navigation = navigationSections.flatMap(section => section.items)

const logout = () => {
    router.post(route('logout'))
}

const stopImpersonating = () => {
    router.get(route('superadmin.stop-impersonating'))
}
</script>

<template>
    <div class="min-h-screen bg-gray-900">
        <!-- Impersonation Banner -->
        <div v-if="isImpersonating" class="bg-amber-500 text-black px-4 py-2 text-center text-sm font-medium">
            <span>Vous êtes connecté en tant qu'un autre utilisateur.</span>
            <button @click="stopImpersonating" class="ml-4 underline hover:no-underline">
                Retourner à mon compte
            </button>
        </div>

        <!-- Mobile sidebar -->
        <div class="lg:hidden">
            <div v-if="sidebarOpen" class="fixed inset-0 z-50 flex">
                <div class="fixed inset-0 bg-gray-900/80" @click="sidebarOpen = false"></div>
                <div class="relative flex w-full max-w-xs flex-1 flex-col bg-gray-800">
                    <div class="absolute top-0 right-0 -mr-12 pt-2">
                        <button @click="sidebarOpen = false" class="ml-1 flex h-10 w-10 items-center justify-center rounded-full">
                            <XMarkIcon class="h-6 w-6 text-white" />
                        </button>
                    </div>
                    <div class="flex h-16 shrink-0 items-center px-6 border-b border-gray-700">
                        <span class="text-xl font-bold text-white">Boxibox</span>
                        <span class="ml-2 px-2 py-0.5 bg-purple-600 text-xs rounded text-white">SuperAdmin</span>
                    </div>
                    <nav class="mt-5 flex-1 space-y-1 px-3">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            :class="[
                                item.current
                                    ? 'bg-purple-600 text-white'
                                    : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                                'group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors'
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    item.current ? 'text-white' : 'text-gray-400 group-hover:text-white',
                                    'mr-3 h-5 w-5 flex-shrink-0'
                                ]"
                            />
                            {{ item.name }}
                        </Link>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex min-h-0 flex-1 flex-col bg-gray-800 border-r border-gray-700">
                <div class="flex h-16 shrink-0 items-center px-6 border-b border-gray-700">
                    <span class="text-xl font-bold text-white">Boxibox</span>
                    <span class="ml-2 px-2 py-0.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-xs rounded-full text-white font-semibold">SuperAdmin</span>
                </div>
                <nav class="mt-3 flex-1 space-y-4 px-3 overflow-y-auto">
                    <div v-for="section in navigationSections" :key="section.title">
                        <p class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            {{ section.title }}
                        </p>
                        <div class="space-y-1">
                            <Link
                                v-for="item in section.items"
                                :key="item.name"
                                :href="item.href"
                                :class="[
                                    item.current
                                        ? 'bg-purple-600 text-white'
                                        : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                                    'group flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors'
                                ]"
                            >
                                <div class="flex items-center">
                                    <component
                                        :is="item.icon"
                                        :class="[
                                            item.current ? 'text-white' : 'text-gray-400 group-hover:text-white',
                                            'mr-3 h-5 w-5 flex-shrink-0'
                                        ]"
                                    />
                                    {{ item.name }}
                                </div>
                                <span
                                    v-if="item.badge"
                                    :class="[
                                        item.badgeColor === 'red' ? 'bg-red-500' : 'bg-purple-500',
                                        'text-white text-xs font-bold px-2 py-0.5 rounded-full'
                                    ]"
                                >
                                    {{ item.badge }}
                                </span>
                            </Link>
                        </div>
                    </div>
                </nav>
                <div class="border-t border-gray-700 p-4">
                    <div class="flex items-center gap-3 mb-3 px-3 py-2">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-white font-bold text-sm">
                            {{ user?.name?.charAt(0) || 'A' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ user?.name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ user?.email }}</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-medium text-gray-300 hover:bg-red-600/20 hover:text-red-400 transition-colors"
                    >
                        <ArrowLeftOnRectangleIcon class="mr-3 h-5 w-5" />
                        Déconnexion
                    </button>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top navbar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-700 bg-gray-800 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button @click="sidebarOpen = true" class="text-gray-400 lg:hidden">
                    <Bars3Icon class="h-6 w-6" />
                </button>

                <div class="h-6 w-px bg-gray-700 lg:hidden"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Search -->
                    <form class="relative flex flex-1" action="#" method="GET">
                        <MagnifyingGlassIcon class="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-gray-500 ml-3" />
                        <input
                            type="search"
                            name="search"
                            placeholder="Rechercher..."
                            class="block h-full w-full border-0 bg-gray-700 py-0 pl-10 pr-0 text-white placeholder:text-gray-400 focus:ring-0 sm:text-sm rounded-lg"
                        />
                    </form>

                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Notifications -->
                        <button class="text-gray-400 hover:text-gray-300">
                            <BellIcon class="h-6 w-6" />
                        </button>

                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-700"></div>

                        <!-- User menu -->
                        <div class="relative">
                            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-x-2">
                                <UserCircleIcon class="h-8 w-8 text-gray-400" />
                                <span class="hidden lg:flex lg:items-center">
                                    <span class="text-sm font-medium text-white">{{ user?.name }}</span>
                                    <ChevronDownIcon class="ml-2 h-4 w-4 text-gray-400" />
                                </span>
                            </button>

                            <div
                                v-if="userMenuOpen"
                                @click="userMenuOpen = false"
                                class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-gray-700 py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                            >
                                <a href="#" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">Mon profil</a>
                                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-600">
                                    Déconnexion
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main class="py-8">
                <div class="px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
