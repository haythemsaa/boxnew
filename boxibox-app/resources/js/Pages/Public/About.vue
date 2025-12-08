<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="py-24 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-6">
                        Notre mission :
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            simplifier le self-storage
                        </span>
                    </h1>
                    <p class="text-xl text-gray-600">
                        Nous construisons les outils qui permettent aux opérateurs de self-storage de se concentrer sur ce qui compte vraiment : leurs clients.
                    </p>
                </div>
            </div>
        </section>

        <!-- Story Section -->
        <section class="py-24 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-6">Notre histoire</h2>
                        <div class="space-y-4 text-gray-600">
                            <p>
                                Fondée en 2023, Boxibox est née d'un constat simple : les logiciels de gestion de self-storage existants n'étaient pas adaptés aux besoins des opérateurs modernes.
                            </p>
                            <p>
                                Notre équipe, composée d'experts en technologie et d'anciens professionnels du self-storage, a décidé de créer la solution que nous aurions voulu avoir.
                            </p>
                            <p>
                                Aujourd'hui, Boxibox accompagne des centaines d'opérateurs en France et en Europe dans leur transformation digitale.
                            </p>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-100 to-purple-100 rounded-2xl aspect-video flex items-center justify-center">
                        <div class="text-center p-8">
                            <svg class="w-16 h-16 mx-auto text-indigo-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <p class="text-gray-500 text-sm">Notre équipe</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Nos valeurs</h2>
                    <p class="text-xl text-gray-600">Ce qui guide chacune de nos décisions.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl p-8 text-center">
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Simplicité</h3>
                        <p class="text-gray-600">
                            Nous croyons que les meilleurs outils sont ceux qui s'effacent. Notre logiciel doit être intuitif et ne jamais vous faire perdre de temps.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl p-8 text-center">
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Partenariat</h3>
                        <p class="text-gray-600">
                            Votre succès est notre succès. Nous ne vendons pas juste un logiciel, nous construisons une relation de confiance à long terme.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl p-8 text-center">
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Innovation</h3>
                        <p class="text-gray-600">
                            Nous investissons continuellement dans la R&D pour vous apporter les dernières technologies : IA, IoT, automation.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                    <div>
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">2023</p>
                        <p class="text-gray-600">Année de création</p>
                    </div>
                    <div>
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">500+</p>
                        <p class="text-gray-600">Clients actifs</p>
                    </div>
                    <div>
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">15</p>
                        <p class="text-gray-600">Pays couverts</p>
                    </div>
                    <div>
                        <p class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">4.9/5</p>
                        <p class="text-gray-600">Note client</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Notre équipe</h2>
                    <p class="text-xl text-gray-600">Des experts passionnés par le self-storage.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="member in team" :key="member.name" class="text-center">
                        <div class="w-32 h-32 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold">
                            {{ member.name.charAt(0) }}
                        </div>
                        <h3 class="font-semibold text-gray-900">{{ member.name }}</h3>
                        <p class="text-indigo-600 text-sm mb-2">{{ member.role }}</p>
                        <p class="text-gray-500 text-sm">{{ member.bio }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 bg-gradient-to-br from-indigo-600 to-purple-700">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Rejoignez l'aventure Boxibox
                </h2>
                <p class="text-xl text-indigo-100 mb-8">
                    Découvrez comment nous pouvons transformer votre activité.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link href="/demo" class="bg-white text-indigo-600 hover:bg-gray-100 font-semibold px-8 py-4 rounded-xl transition-colors">
                        Demander une démo
                    </Link>
                    <Link href="/contact" class="border-2 border-white text-white hover:bg-white/10 font-semibold px-8 py-4 rounded-xl transition-colors">
                        Nous contacter
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const team = [
    {
        name: 'Alexandre Martin',
        role: 'CEO & Co-fondateur',
        bio: '15 ans d\'expérience dans le self-storage',
    },
    {
        name: 'Sophie Dubois',
        role: 'CTO & Co-fondatrice',
        bio: 'Ex-Google, experte en SaaS B2B',
    },
    {
        name: 'Pierre Bernard',
        role: 'Head of Product',
        bio: 'Passionné par l\'UX et l\'innovation',
    },
    {
        name: 'Marie Laurent',
        role: 'Head of Customer Success',
        bio: 'Dédiée à votre réussite',
    },
]
</script>
