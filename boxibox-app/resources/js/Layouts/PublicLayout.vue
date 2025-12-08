<template>
    <div class="min-h-screen bg-white">
        <!-- Navigation -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Boxibox
                        </span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center gap-8">
                        <Link href="/features" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                            Fonctionnalités
                        </Link>
                        <Link href="/pricing" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                            Tarifs
                        </Link>
                        <Link href="/calculator" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                            Calculateur
                        </Link>
                        <Link href="/about" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                            À propos
                        </Link>
                        <Link href="/contact" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                            Contact
                        </Link>
                    </div>

                    <!-- CTA Buttons & Language Switcher -->
                    <div class="hidden md:flex items-center gap-4">
                        <LanguageSwitcher :show-label="false" />
                        <Link href="/login" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                            {{ t('nav.login') }}
                        </Link>
                        <Link href="/demo" class="btn-primary">
                            {{ t('nav.demo') }}
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                        <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div v-if="mobileMenuOpen" class="md:hidden bg-white border-t border-gray-100">
                <div class="px-4 py-4 space-y-3">
                    <Link href="/features" class="block px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Fonctionnalités
                    </Link>
                    <Link href="/pricing" class="block px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Tarifs
                    </Link>
                    <Link href="/calculator" class="block px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Calculateur
                    </Link>
                    <Link href="/contact" class="block px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                        Contact
                    </Link>
                    <div class="pt-4 border-t border-gray-100 space-y-3">
                        <Link href="/login" class="block px-4 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg">
                            Connexion
                        </Link>
                        <Link href="/demo" class="btn-primary w-full text-center">
                            Demander une démo
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="pt-16">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                    <!-- Brand -->
                    <div class="col-span-1">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <span class="text-xl font-bold">Boxibox</span>
                        </div>
                        <p class="text-gray-400 text-sm mb-6">
                            La solution SaaS moderne pour gérer votre activité de self-storage. Automatisez vos opérations et boostez vos revenus.
                        </p>
                        <div class="flex gap-4">
                            <a href="#" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Product -->
                    <div>
                        <h3 class="font-semibold mb-4">Produit</h3>
                        <ul class="space-y-3 text-sm">
                            <li><Link href="/features" class="text-gray-400 hover:text-white transition-colors">Fonctionnalités</Link></li>
                            <li><Link href="/pricing" class="text-gray-400 hover:text-white transition-colors">Tarifs</Link></li>
                            <li><Link href="/calculator" class="text-gray-400 hover:text-white transition-colors">Calculateur</Link></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Intégrations</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">API</a></li>
                        </ul>
                    </div>

                    <!-- Resources -->
                    <div>
                        <h3 class="font-semibold mb-4">Ressources</h3>
                        <ul class="space-y-3 text-sm">
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Documentation</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Blog</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Guides</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Webinaires</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Changelog</a></li>
                        </ul>
                    </div>

                    <!-- Company -->
                    <div>
                        <h3 class="font-semibold mb-4">Entreprise</h3>
                        <ul class="space-y-3 text-sm">
                            <li><Link href="/about" class="text-gray-400 hover:text-white transition-colors">À propos</Link></li>
                            <li><Link href="/contact" class="text-gray-400 hover:text-white transition-colors">Contact</Link></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Carrières</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Mentions légales</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">RGPD</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-400 text-sm">
                        &copy; {{ new Date().getFullYear() }} Boxibox. Tous droits réservés.
                    </p>
                    <div class="flex gap-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Politique de confidentialité</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">CGU</a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">Cookies</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue'
import { useTranslation } from '@/composables/useTranslation'

const { t } = useTranslation()
const mobileMenuOpen = ref(false)
</script>
