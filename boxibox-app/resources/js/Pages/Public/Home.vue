<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="relative min-h-[90vh] flex items-center overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-40">
                <div class="absolute top-20 left-10 w-72 h-72 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
                <div class="absolute top-40 right-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
                <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Text Content -->
                    <div class="text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-100 rounded-full text-indigo-700 text-sm font-medium mb-6">
                            <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                            Leader du Self-Storage SaaS en France
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                            Gérez votre
                            <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                Self-Storage
                            </span>
                            comme un pro
                        </h1>

                        <p class="text-xl text-gray-600 mb-8 max-w-xl mx-auto lg:mx-0">
                            La plateforme tout-en-un pour automatiser vos opérations, maximiser votre occupation et booster vos revenus.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <Link href="/demo" class="btn-primary btn-lg">
                                Demander une démo
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                            <Link href="/calculator" class="btn-outline btn-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Calculer mes économies
                            </Link>
                        </div>

                        <!-- Trust Badges -->
                        <div class="mt-12 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Essai gratuit 14 jours
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Sans carte bancaire
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Support inclus
                            </div>
                        </div>
                    </div>

                    <!-- Hero Image / Dashboard Preview -->
                    <div class="relative">
                        <div class="bg-white rounded-2xl shadow-2xl p-2 transform rotate-1 hover:rotate-0 transition-transform duration-500">
                            <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl aspect-video flex items-center justify-center">
                                <div class="text-center p-8">
                                    <svg class="w-16 h-16 mx-auto text-indigo-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-gray-500 text-sm">Aperçu du Dashboard</p>
                                </div>
                            </div>
                        </div>
                        <!-- Floating Stats -->
                        <div class="absolute -bottom-6 -left-6 bg-white rounded-xl shadow-lg p-4 flex items-center gap-3">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">+23%</p>
                                <p class="text-xs text-gray-500">Taux d'occupation</p>
                            </div>
                        </div>
                        <div class="absolute -top-4 -right-4 bg-white rounded-xl shadow-lg p-4 flex items-center gap-3">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-900">15h</p>
                                <p class="text-xs text-gray-500">Gagnées / semaine</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-16 bg-white border-y border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            {{ formatNumber(stats.total_sites) }}+
                        </p>
                        <p class="text-gray-600 mt-1">Sites gérés</p>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            {{ formatNumber(stats.total_boxes) }}+
                        </p>
                        <p class="text-gray-600 mt-1">Boxes actives</p>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            {{ formatNumber(stats.total_customers) }}+
                        </p>
                        <p class="text-gray-600 mt-1">Clients satisfaits</p>
                    </div>
                    <div class="text-center">
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            {{ stats.average_rating }}/5
                        </p>
                        <p class="text-gray-600 mt-1">Note moyenne</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                        Tout ce dont vous avez besoin
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Une plateforme complète pour gérer chaque aspect de votre activité de self-storage.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        v-for="(feature, index) in features"
                        :key="index"
                        class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 group"
                    >
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <component :is="getIcon(feature.icon)" class="w-7 h-7 text-indigo-600" />
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ feature.title }}</h3>
                        <p class="text-gray-600">{{ feature.description }}</p>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <Link href="/features" class="btn-primary">
                        Découvrir toutes les fonctionnalités
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                        Ce que nos clients disent
                    </h2>
                    <p class="text-xl text-gray-600">
                        Rejoignez des centaines d'opérateurs qui nous font confiance.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div
                        v-for="(testimonial, index) in testimonials"
                        :key="index"
                        class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 border border-gray-100"
                    >
                        <!-- Stars -->
                        <div class="flex gap-1 mb-4">
                            <svg v-for="i in testimonial.rating" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>

                        <p class="text-gray-700 mb-6 italic">"{{ testimonial.quote }}"</p>

                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                                {{ testimonial.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ testimonial.name }}</p>
                                <p class="text-sm text-gray-500">{{ testimonial.company }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                        Tarifs simples et transparents
                    </h2>
                    <p class="text-xl text-gray-600">
                        Choisissez le plan adapté à votre croissance. Sans frais cachés.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div
                        v-for="(plan, index) in pricing"
                        :key="index"
                        :class="[
                            'bg-white rounded-2xl p-8 relative',
                            plan.popular
                                ? 'ring-2 ring-indigo-500 shadow-xl scale-105'
                                : 'border border-gray-200 shadow-sm'
                        ]"
                    >
                        <div v-if="plan.popular" class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                            <span class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium px-4 py-1 rounded-full">
                                Populaire
                            </span>
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ plan.name }}</h3>
                        <p class="text-gray-500 mb-6">{{ plan.description }}</p>

                        <div class="mb-6">
                            <span class="text-4xl font-bold text-gray-900">{{ plan.price }}€</span>
                            <span class="text-gray-500">/{{ plan.period }}</span>
                        </div>

                        <ul class="space-y-3 mb-8">
                            <li v-for="(feature, fIndex) in plan.features" :key="fIndex" class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-600">{{ feature }}</span>
                            </li>
                        </ul>

                        <Link
                            :href="plan.popular ? '/demo' : '/contact'"
                            :class="plan.popular ? 'btn-primary w-full' : 'btn-outline w-full'"
                        >
                            {{ plan.cta }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 bg-gradient-to-br from-indigo-600 to-purple-700 relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect width="100" height="100" fill="url(#grid)" />
                </svg>
            </div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Prêt à transformer votre self-storage ?
                </h2>
                <p class="text-xl text-indigo-100 mb-8">
                    Rejoignez les opérateurs qui ont choisi Boxibox pour automatiser leur activité.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link href="/demo" class="bg-white text-indigo-600 hover:bg-gray-100 font-semibold px-8 py-4 rounded-xl transition-colors">
                        Demander une démo gratuite
                    </Link>
                    <Link href="/contact" class="border-2 border-white text-white hover:bg-white/10 font-semibold px-8 py-4 rounded-xl transition-colors">
                        Contacter l'équipe
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { h } from 'vue'

const props = defineProps({
    stats: Object,
    features: Array,
    testimonials: Array,
    pricing: Array,
})

const formatNumber = (num) => {
    if (!num) return '0'
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'k'
    }
    return num.toString()
}

// Icon components mapping
const icons = {
    cube: {
        render() {
            return h('svg', {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                class: 'w-7 h-7'
            }, [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
                })
            ])
        }
    },
    users: {
        render() {
            return h('svg', {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                class: 'w-7 h-7'
            }, [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
                })
            ])
        }
    },
    'credit-card': {
        render() {
            return h('svg', {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                class: 'w-7 h-7'
            }, [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'
                })
            ])
        }
    },
    'chart-bar': {
        render() {
            return h('svg', {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                class: 'w-7 h-7'
            }, [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
                })
            ])
        }
    },
    'lock-closed': {
        render() {
            return h('svg', {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                class: 'w-7 h-7'
            }, [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
                })
            ])
        }
    },
    'device-mobile': {
        render() {
            return h('svg', {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                class: 'w-7 h-7'
            }, [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': '2',
                    d: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'
                })
            ])
        }
    },
}

const getIcon = (name) => {
    return icons[name] || icons.cube
}
</script>

<style scoped>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(20px, -30px) scale(1.1); }
    50% { transform: translate(-20px, 20px) scale(0.9); }
    75% { transform: translate(30px, 10px) scale(1.05); }
}

.animate-blob {
    animation: blob 8s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

.btn-lg {
    padding-left: 2rem;
    padding-right: 2rem;
    padding-top: 1rem;
    padding-bottom: 1rem;
    font-size: 1rem;
    line-height: 1.5rem;
}
</style>
