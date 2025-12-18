<template>
    <div :class="['min-h-screen transition-colors duration-300', isDarkMode ? 'dark bg-gray-900' : 'bg-gray-100']">
        <!-- Top Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-14 md:h-16">
                    <!-- Logo & Brand -->
                    <div class="flex items-center">
                        <Link :href="route('customer.portal.dashboard')" class="flex items-center gap-2 md:gap-3">
                            <div class="w-9 h-9 md:w-10 md:h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                                <i class="fas fa-box text-white text-base md:text-lg"></i>
                            </div>
                            <span class="text-lg md:text-xl font-bold text-gray-900 dark:text-white hidden sm:block">
                                BoxiBox
                            </span>
                        </Link>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden lg:flex items-center gap-1">
                        <Link
                            v-for="item in navigation"
                            :key="item.route"
                            :href="route(item.route)"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                                isActive(item.route)
                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                            ]"
                        >
                            <i :class="item.icon" class="mr-2"></i>
                            {{ item.label }}
                        </Link>
                    </div>

                    <!-- Right Side -->
                    <div class="flex items-center gap-2 md:gap-4">
                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggleDarkMode"
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :title="isDarkMode ? 'Mode clair' : 'Mode sombre'"
                        >
                            <i :class="isDarkMode ? 'fas fa-sun text-yellow-400' : 'fas fa-moon'" class="text-lg"></i>
                        </button>

                        <!-- Quick Pay Button (Desktop) -->
                        <Link
                            v-if="hasPendingInvoices"
                            :href="route('customer.portal.invoices', { status: 'pending' })"
                            class="hidden md:flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition text-sm font-medium shadow-lg shadow-green-500/30"
                        >
                            <i class="fas fa-credit-card"></i>
                            Payer
                        </Link>

                        <!-- Notifications -->
                        <button class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <i class="fas fa-bell text-lg"></i>
                            <span v-if="notificationCount > 0" class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold animate-pulse">
                                {{ notificationCount > 9 ? '9+' : notificationCount }}
                            </span>
                        </button>

                        <!-- User Menu -->
                        <div class="relative" v-click-outside="() => showUserMenu = false">
                            <button
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            >
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg">
                                    {{ customerInitials }}
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
                            </button>

                            <!-- Dropdown -->
                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div
                                    v-if="showUserMenu"
                                    class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50 overflow-hidden"
                                >
                                    <!-- User Info -->
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ customerName }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ customerEmail }}</p>
                                    </div>

                                    <Link
                                        :href="route('customer.portal.profile')"
                                        class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        @click="showUserMenu = false"
                                    >
                                        <i class="fas fa-user-circle w-5 text-gray-400"></i>
                                        Mon profil
                                    </Link>
                                    <Link
                                        :href="route('customer.portal.documents')"
                                        class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        @click="showUserMenu = false"
                                    >
                                        <i class="fas fa-folder-open w-5 text-gray-400"></i>
                                        Mes documents
                                    </Link>
                                    <Link
                                        :href="route('customer.portal.autopay')"
                                        class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        @click="showUserMenu = false"
                                    >
                                        <i class="fas fa-sync-alt w-5 text-gray-400"></i>
                                        Autopay
                                    </Link>
                                    <Link
                                        :href="route('customer.portal.referral')"
                                        class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        @click="showUserMenu = false"
                                    >
                                        <i class="fas fa-gift w-5 text-gray-400"></i>
                                        Parrainage
                                        <span class="ml-auto px-2 py-0.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-400 text-xs rounded-full">50€</span>
                                    </Link>
                                    <Link
                                        :href="route('customer.portal.size-calculator')"
                                        class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        @click="showUserMenu = false"
                                    >
                                        <i class="fas fa-calculator w-5 text-gray-400"></i>
                                        Calculateur
                                    </Link>
                                    <Link
                                        :href="route('customer.portal.insurance')"
                                        class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        @click="showUserMenu = false"
                                    >
                                        <i class="fas fa-shield-alt w-5 text-gray-400"></i>
                                        Assurance
                                    </Link>
                                    <Link
                                        :href="route('customer.portal.notification-preferences')"
                                        class="flex items-center gap-3 px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                        @click="showUserMenu = false"
                                    >
                                        <i class="fas fa-bell w-5 text-gray-400"></i>
                                        Notifications
                                    </Link>
                                    <hr class="my-2 border-gray-100 dark:border-gray-700">
                                    <button
                                        @click="logout"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                                    >
                                        <i class="fas fa-sign-out-alt w-5"></i>
                                        Déconnexion
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                <div
                    v-if="$page.props.flash?.success"
                    class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl text-green-700 dark:text-green-300 flex items-center gap-3"
                >
                    <i class="fas fa-check-circle text-green-500"></i>
                    {{ $page.props.flash.success }}
                </div>
                <div
                    v-if="$page.props.flash?.error"
                    class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl text-red-700 dark:text-red-300 flex items-center gap-3"
                >
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    {{ $page.props.flash.error }}
                </div>
            </div>
        </Transition>

        <!-- Main Content - Add padding bottom for mobile nav -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 lg:pb-8">
            <slot />
        </main>

        <!-- Footer (Desktop only) -->
        <footer class="hidden lg:block bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} BoxiBox. Tous droits réservés.
                    </p>
                    <div class="flex gap-6 text-sm">
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            Conditions
                        </a>
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            Confidentialité
                        </a>
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">
                            Contact
                        </a>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Mobile Bottom Navigation -->
        <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-50 safe-area-bottom">
            <div class="flex justify-around items-center h-16">
                <Link
                    v-for="item in mobileNavigation"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'flex flex-col items-center justify-center w-full h-full transition-all duration-200',
                        isActive(item.route)
                            ? 'text-indigo-600 dark:text-indigo-400'
                            : 'text-gray-500 dark:text-gray-400'
                    ]"
                >
                    <div :class="[
                        'flex items-center justify-center w-10 h-10 rounded-2xl transition-all duration-200',
                        isActive(item.route) ? 'bg-indigo-100 dark:bg-indigo-900/50 scale-110' : ''
                    ]">
                        <i :class="[item.icon, 'text-lg']"></i>
                    </div>
                    <span class="text-[10px] font-medium mt-0.5">{{ item.shortLabel }}</span>
                </Link>
            </div>
        </nav>

        <!-- Floating Action Button for Quick Pay (Mobile) -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 scale-50"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-50"
        >
            <Link
                v-if="hasPendingInvoices && !isActive('customer.portal.invoices')"
                :href="route('customer.portal.invoices', { status: 'pending' })"
                class="lg:hidden fixed bottom-20 right-4 w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full shadow-lg shadow-green-500/40 flex items-center justify-center z-40 animate-bounce-slow"
            >
                <i class="fas fa-euro-sign text-xl"></i>
            </Link>
        </Transition>

        <!-- Floating Chatbot Button -->
        <button
            @click="showChatbot = !showChatbot"
            :class="[
                'fixed z-50 w-14 h-14 rounded-full shadow-xl flex items-center justify-center transition-all duration-300 transform hover:scale-110',
                showChatbot
                    ? 'bg-red-500 hover:bg-red-600 bottom-24 lg:bottom-6 right-4'
                    : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 bottom-24 lg:bottom-6 right-4'
            ]"
        >
            <i :class="showChatbot ? 'fas fa-times' : 'fas fa-comment-dots'" class="text-white text-xl"></i>
            <span v-if="!showChatbot" class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white animate-pulse"></span>
        </button>

        <!-- Chatbot Panel -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
        >
            <div
                v-if="showChatbot"
                class="fixed bottom-40 lg:bottom-24 right-4 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
            >
                <!-- Chatbot Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-robot text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">Assistant BoxiBox</h3>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                <span class="text-white/80 text-xs">En ligne 24/7</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chatbot Messages -->
                <div class="h-64 sm:h-80 p-4 overflow-y-auto bg-gray-50 dark:bg-gray-900/50">
                    <!-- Welcome Message -->
                    <div class="flex gap-2 mb-4">
                        <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex-shrink-0 flex items-center justify-center">
                            <i class="fas fa-robot text-indigo-600 dark:text-indigo-400 text-sm"></i>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-none px-4 py-3 shadow-sm max-w-[85%]">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Bonjour {{ customerName.split(' ')[0] }} ! 👋 Je suis votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?
                            </p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="space-y-2 mb-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium ml-10">Questions fréquentes :</p>
                        <div class="flex flex-wrap gap-2 ml-10">
                            <button
                                @click="chatbotAction('access')"
                                class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full text-xs font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition"
                            >
                                <i class="fas fa-qrcode mr-1"></i> Code d'accès
                            </button>
                            <button
                                @click="chatbotAction('invoice')"
                                class="px-3 py-1.5 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full text-xs font-medium hover:bg-green-100 dark:hover:bg-green-900/50 transition"
                            >
                                <i class="fas fa-file-invoice mr-1"></i> Mes factures
                            </button>
                            <button
                                @click="chatbotAction('contract')"
                                class="px-3 py-1.5 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full text-xs font-medium hover:bg-purple-100 dark:hover:bg-purple-900/50 transition"
                            >
                                <i class="fas fa-file-contract mr-1"></i> Mon contrat
                            </button>
                            <button
                                @click="chatbotAction('support')"
                                class="px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full text-xs font-medium hover:bg-amber-100 dark:hover:bg-amber-900/50 transition"
                            >
                                <i class="fas fa-headset mr-1"></i> Parler à un agent
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Chatbot Input -->
                <div class="p-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <input
                            v-model="chatbotMessage"
                            @keyup.enter="sendChatbotMessage"
                            type="text"
                            placeholder="Écrivez votre message..."
                            class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-indigo-500"
                        />
                        <button
                            @click="sendChatbotMessage"
                            class="p-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition"
                        >
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const showUserMenu = ref(false);
const isDarkMode = ref(false);

// Desktop navigation (full)
const navigation = [
    { route: 'customer.portal.dashboard', label: 'Tableau de bord', icon: 'fas fa-home' },
    { route: 'customer.portal.contracts', label: 'Contrats', icon: 'fas fa-file-contract' },
    { route: 'customer.portal.invoices', label: 'Factures', icon: 'fas fa-file-invoice' },
    { route: 'customer.portal.documents', label: 'Documents', icon: 'fas fa-folder-open' },
    { route: 'customer.portal.insurance', label: 'Assurance', icon: 'fas fa-shield-alt' },
    { route: 'customer.portal.access.index', label: 'Accès 24/7', icon: 'fas fa-key' },
    { route: 'customer.portal.support', label: 'Support', icon: 'fas fa-headset' },
];

// Mobile bottom navigation (5 items max for best UX)
const mobileNavigation = [
    { route: 'customer.portal.dashboard', shortLabel: 'Accueil', icon: 'fas fa-home' },
    { route: 'customer.portal.contracts', shortLabel: 'Contrats', icon: 'fas fa-box' },
    { route: 'customer.portal.access.index', shortLabel: 'Accès', icon: 'fas fa-qrcode' },
    { route: 'customer.portal.support', shortLabel: 'Support', icon: 'fas fa-headset' },
    { route: 'customer.portal.profile', shortLabel: 'Profil', icon: 'fas fa-user' },
];

// Chatbot state
const showChatbot = ref(false);
const chatbotMessage = ref('');

// Chatbot actions
const chatbotAction = (action) => {
    switch (action) {
        case 'access':
            router.visit(route('customer.portal.access.index'));
            showChatbot.value = false;
            break;
        case 'invoice':
            router.visit(route('customer.portal.invoices'));
            showChatbot.value = false;
            break;
        case 'contract':
            router.visit(route('customer.portal.contracts'));
            showChatbot.value = false;
            break;
        case 'support':
            router.visit(route('customer.portal.support'));
            showChatbot.value = false;
            break;
    }
};

const sendChatbotMessage = () => {
    if (!chatbotMessage.value.trim()) return;
    // Pour l'instant, rediriger vers le support avec le message
    router.visit(route('customer.portal.support'), {
        data: { message: chatbotMessage.value }
    });
    chatbotMessage.value = '';
    showChatbot.value = false;
};

const customerName = computed(() => page.props.customer?.name || 'Client');
const customerEmail = computed(() => page.props.customer?.email || '');
const customerInitials = computed(() => {
    const name = customerName.value;
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});

const hasPendingInvoices = computed(() => (page.props.stats?.pending_invoices || 0) > 0);
const notificationCount = computed(() => page.props.notificationCount || 0);

const isActive = (routeName) => {
    return route().current(routeName) || route().current(routeName + '.*');
};

const logout = () => {
    router.post(route('customer.portal.logout'));
};

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('darkMode', isDarkMode.value);

    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

// Initialize dark mode from localStorage
onMounted(() => {
    const savedDarkMode = localStorage.getItem('darkMode');
    if (savedDarkMode === 'true') {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    } else if (savedDarkMode === null && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});

// Click outside directive
const vClickOutside = {
    mounted(el, binding) {
        el._clickOutside = (event) => {
            if (!el.contains(event.target)) {
                binding.value();
            }
        };
        document.addEventListener('click', el._clickOutside);
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutside);
    }
};
</script>

<style scoped>
/* Safe area for iOS devices with notch/home indicator */
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom, 0);
}

/* Slow bounce animation for FAB */
@keyframes bounce-slow {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.animate-bounce-slow {
    animation: bounce-slow 2s ease-in-out infinite;
}
</style>
