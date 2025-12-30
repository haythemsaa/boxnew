<template>
    <MobileLayout title="Plus">
        <!-- User Info -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold text-primary-600">{{ userInitial }}</span>
                </div>
                <div class="ml-4 flex-1">
                    <h2 class="text-lg font-bold text-gray-900">{{ $page.props.auth.user?.name }}</h2>
                    <p class="text-gray-500">{{ $page.props.auth.user?.email }}</p>
                </div>
                <Link :href="route('mobile.profile')" class="p-2">
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </Link>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
                <CubeIcon class="w-6 h-6 mx-auto text-primary-500 mb-1" />
                <p class="text-lg font-bold text-gray-900">{{ stats.boxes }}</p>
                <p class="text-xs text-gray-500">Box</p>
            </div>
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
                <DocumentTextIcon class="w-6 h-6 mx-auto text-blue-500 mb-1" />
                <p class="text-lg font-bold text-gray-900">{{ stats.contracts }}</p>
                <p class="text-xs text-gray-500">Contrats</p>
            </div>
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
                <CurrencyEuroIcon class="w-6 h-6 mx-auto text-green-500 mb-1" />
                <p class="text-lg font-bold text-gray-900">{{ formatCurrency(stats.total_paid) }}</p>
                <p class="text-xs text-gray-500">Paye</p>
            </div>
        </div>

        <!-- Menu Sections -->
        <div class="space-y-4">
            <!-- Account Section -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                    Mon compte
                </h3>
                <div class="divide-y divide-gray-100">
                    <Link
                        :href="route('mobile.profile')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <UserCircleIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Mon profil</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                    <Link
                        :href="route('mobile.documents')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <FolderIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Mes documents</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                    <Link
                        :href="route('mobile.payment-methods')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <CreditCardIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Moyens de paiement</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                </div>
            </div>

            <!-- Services Section -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                    Services
                </h3>
                <div class="divide-y divide-gray-100">
                    <Link
                        :href="route('mobile.reserve')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <PlusCircleIcon class="w-6 h-6 text-primary-500 mr-3" />
                        <span class="flex-1 text-gray-900">Reserver un nouveau box</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                    <a
                        href="/calculator"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <CalculatorIcon class="w-6 h-6 text-blue-500 mr-3" />
                        <span class="flex-1 text-gray-900">Calculateur de taille</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </a>
                    <Link
                        :href="route('mobile.insurance')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <ShieldCheckIcon class="w-6 h-6 text-green-500 mr-3" />
                        <span class="flex-1 text-gray-900">Assurance</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                </div>
            </div>

            <!-- Support Section -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                    Aide & Support
                </h3>
                <div class="divide-y divide-gray-100">
                    <Link
                        :href="route('mobile.support')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <ChatBubbleLeftRightIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Contacter le support</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                    <Link
                        :href="route('mobile.faq')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <QuestionMarkCircleIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">FAQ</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                    <a
                        href="tel:0800123456"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <PhoneIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Appeler</span>
                        <span class="text-sm text-gray-500">0 800 123 456</span>
                    </a>
                </div>
            </div>

            <!-- Install App Banner -->
            <div v-if="!isInstalled" class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 rounded-2xl shadow-lg p-4 mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4">
                        <DevicePhoneMobileIcon class="w-7 h-7 text-white" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-white">Installer l'application</h4>
                        <p class="text-purple-100 text-sm">Acces rapide depuis votre ecran</p>
                    </div>
                    <button
                        @click="installApp"
                        class="px-4 py-2 bg-white text-purple-600 rounded-xl font-semibold text-sm"
                    >
                        Installer
                    </button>
                </div>
            </div>

            <!-- iOS Instructions Modal -->
            <div v-if="showIOSModal" class="fixed inset-0 z-50 flex items-end justify-center" @click.self="showIOSModal = false">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="relative bg-white w-full rounded-t-3xl p-6 pb-10">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <DevicePhoneMobileIcon class="w-8 h-8 text-purple-600" />
                        </div>
                        <h3 class="text-xl font-bold">Installer sur Android</h3>
                    </div>
                    <div class="space-y-4 text-left">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-purple-600 font-bold">1</span>
                            </div>
                            <p class="text-gray-700 pt-1">Appuyez sur le menu <strong>⋮</strong> (3 points) en haut a droite de Chrome</p>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-purple-600 font-bold">2</span>
                            </div>
                            <p class="text-gray-700 pt-1">Selectionnez <strong>"Installer l'application"</strong> ou <strong>"Ajouter a l'ecran d'accueil"</strong></p>
                        </div>
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                <span class="text-purple-600 font-bold">3</span>
                            </div>
                            <p class="text-gray-700 pt-1">Confirmez en appuyant sur <strong>"Installer"</strong></p>
                        </div>
                    </div>
                    <button @click="showIOSModal = false" class="w-full mt-6 py-3 bg-purple-600 text-white rounded-xl font-semibold">
                        Compris !
                    </button>
                </div>
            </div>

            <!-- Settings Section -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                    Parametres
                </h3>
                <div class="divide-y divide-gray-100">
                    <Link
                        :href="route('mobile.settings')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <Cog6ToothIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Parametres</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                    <Link
                        :href="route('mobile.notifications')"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <BellIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Notifications</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </Link>
                    <button
                        @click="toggleDarkMode"
                        class="flex items-center px-4 py-3 hover:bg-gray-50 w-full text-left"
                    >
                        <MoonIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Mode sombre</span>
                        <div
                            class="w-11 h-6 rounded-full relative transition-colors"
                            :class="darkMode ? 'bg-primary-600' : 'bg-gray-300'"
                        >
                            <div
                                class="w-5 h-5 bg-white rounded-full absolute top-0.5 transition-all shadow"
                                :class="darkMode ? 'right-0.5' : 'left-0.5'"
                            ></div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Legal Section -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <h3 class="px-4 py-3 text-sm font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                    Legal
                </h3>
                <div class="divide-y divide-gray-100">
                    <a
                        href="#"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <DocumentTextIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Conditions generales</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </a>
                    <a
                        href="#"
                        class="flex items-center px-4 py-3 hover:bg-gray-50"
                    >
                        <ShieldCheckIcon class="w-6 h-6 text-gray-400 mr-3" />
                        <span class="flex-1 text-gray-900">Politique de confidentialite</span>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </a>
                </div>
            </div>

            <!-- Logout -->
            <Link
                :href="route('mobile.logout')"
                method="post"
                as="button"
                class="w-full bg-white rounded-2xl shadow-sm p-4 flex items-center justify-center text-red-600 font-semibold"
            >
                <ArrowRightOnRectangleIcon class="w-6 h-6 mr-2" />
                Deconnexion
            </Link>

            <!-- App Version -->
            <div class="text-center py-4 text-gray-400 text-sm">
                <p>Boxibox v1.0.0</p>
                <p class="mt-1">Made with love</p>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    ChevronRightIcon,
    CubeIcon,
    DocumentTextIcon,
    CurrencyEuroIcon,
    UserCircleIcon,
    FolderIcon,
    CreditCardIcon,
    PlusCircleIcon,
    CalculatorIcon,
    ShieldCheckIcon,
    ChatBubbleLeftRightIcon,
    QuestionMarkCircleIcon,
    PhoneIcon,
    Cog6ToothIcon,
    BellIcon,
    MoonIcon,
    ArrowRightOnRectangleIcon,
    DevicePhoneMobileIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: Object,
})

const page = usePage()
const darkMode = ref(false)
const showIOSModal = ref(false)
const isInstalled = ref(false)
let deferredPrompt = null

const userInitial = computed(() => {
    return page.props.auth?.user?.name?.charAt(0)?.toUpperCase() || 'U'
})

const formatCurrency = (value) => {
    if (value >= 1000) {
        return Math.round(value / 1000) + 'k€'
    }
    return value + '€'
}

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value
    // TODO: Implement dark mode toggle
}

// Check if app is already installed
const checkIfInstalled = () => {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        isInstalled.value = true
    }
    if (window.navigator.standalone === true) {
        isInstalled.value = true
    }
}

// Install app function
const installApp = async () => {
    // Try native prompt first
    if (deferredPrompt) {
        deferredPrompt.prompt()
        const { outcome } = await deferredPrompt.userChoice
        if (outcome === 'accepted') {
            isInstalled.value = true
        }
        deferredPrompt = null
        return
    }

    // Try global install function
    if (window.installPWA) {
        window.installPWA()
        return
    }

    // Show manual instructions
    showIOSModal.value = true
}

onMounted(() => {
    checkIfInstalled()

    // Listen for install prompt
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault()
        deferredPrompt = e
    })

    // Listen for successful install
    window.addEventListener('appinstalled', () => {
        isInstalled.value = true
    })
})
</script>
