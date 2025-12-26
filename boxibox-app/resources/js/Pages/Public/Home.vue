<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-b from-slate-900 via-slate-900 to-indigo-950">
            <!-- Animated Background -->
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-[url('/images/grid.svg')] opacity-20"></div>
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/30 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl animate-pulse animation-delay-2000"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                <div class="text-center">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-full text-indigo-300 text-sm font-medium mb-8 backdrop-blur-sm">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Nouveau: Reservations en ligne avec paiement integre
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold text-white leading-tight mb-6">
                        Le logiciel
                        <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                            tout-en-un
                        </span>
                        <br>pour votre Self-Storage
                    </h1>

                    <p class="text-xl text-slate-300 mb-10 max-w-3xl mx-auto leading-relaxed">
                        Automatisez vos operations, maximisez votre taux d'occupation et boostez vos revenus.
                        Rejoint par <span class="text-white font-semibold">500+ operateurs</span> en France.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                        <Link href="/demo" class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl overflow-hidden transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-indigo-500/25">
                            <span class="relative z-10 flex items-center gap-2">
                                Demander une demo gratuite
                                <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </span>
                        </Link>
                        <Link href="/pricing" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-slate-300 border border-slate-600 rounded-xl hover:bg-slate-800 hover:text-white transition-all duration-300">
                            Voir les tarifs
                        </Link>
                    </div>

                    <!-- Trust Badges -->
                    <div class="flex flex-wrap items-center justify-center gap-8 text-sm text-slate-400">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>14 jours d'essai gratuit</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Sans carte bancaire</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Support francais inclus</span>
                        </div>
                    </div>
                </div>

                <!-- Hero Screenshot - Dashboard Mockup -->
                <div class="mt-16 relative">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent z-10 pointer-events-none"></div>
                    <div class="relative mx-auto max-w-6xl">
                        <!-- Browser Frame -->
                        <div class="bg-slate-800 rounded-t-2xl p-3 flex items-center gap-2">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            </div>
                            <div class="flex-1 text-center">
                                <div class="inline-flex items-center gap-2 px-4 py-1 bg-slate-700/50 rounded-lg text-slate-400 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    app.boxibox.fr/tenant/dashboard
                                </div>
                            </div>
                        </div>
                        <!-- Dashboard Mockup -->
                        <div class="bg-slate-100 rounded-b-2xl shadow-2xl shadow-black/50 overflow-hidden">
                            <div class="flex">
                                <!-- Sidebar -->
                                <div class="w-16 bg-slate-900 p-3 hidden sm:block">
                                    <div class="w-10 h-10 bg-indigo-600 rounded-xl mb-6"></div>
                                    <div class="space-y-4">
                                        <div class="w-10 h-10 bg-slate-700 rounded-lg"></div>
                                        <div class="w-10 h-10 bg-indigo-500/30 rounded-lg border-2 border-indigo-500"></div>
                                        <div class="w-10 h-10 bg-slate-700 rounded-lg"></div>
                                        <div class="w-10 h-10 bg-slate-700 rounded-lg"></div>
                                        <div class="w-10 h-10 bg-slate-700 rounded-lg"></div>
                                    </div>
                                </div>
                                <!-- Main Content -->
                                <div class="flex-1 p-4 sm:p-6">
                                    <!-- Header -->
                                    <div class="flex justify-between items-center mb-6">
                                        <div>
                                            <div class="h-6 w-40 bg-slate-300 rounded mb-2"></div>
                                            <div class="h-4 w-24 bg-slate-200 rounded"></div>
                                        </div>
                                        <div class="flex gap-3">
                                            <div class="w-10 h-10 bg-white rounded-lg shadow"></div>
                                            <div class="w-10 h-10 bg-white rounded-lg shadow"></div>
                                            <div class="w-10 h-10 bg-indigo-600 rounded-full"></div>
                                        </div>
                                    </div>
                                    <!-- Stats Cards -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                                        <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                                    <div class="w-4 h-4 bg-green-500 rounded"></div>
                                                </div>
                                                <span class="text-xs text-slate-500">Occupation</span>
                                            </div>
                                            <div class="text-xl sm:text-2xl font-bold text-slate-900">87%</div>
                                            <div class="text-xs text-green-600">+5% ce mois</div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <div class="w-4 h-4 bg-blue-500 rounded"></div>
                                                </div>
                                                <span class="text-xs text-slate-500">Revenus</span>
                                            </div>
                                            <div class="text-xl sm:text-2xl font-bold text-slate-900">45.2k</div>
                                            <div class="text-xs text-green-600">+12% vs M-1</div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                                    <div class="w-4 h-4 bg-purple-500 rounded"></div>
                                                </div>
                                                <span class="text-xs text-slate-500">Clients</span>
                                            </div>
                                            <div class="text-xl sm:text-2xl font-bold text-slate-900">324</div>
                                            <div class="text-xs text-green-600">+8 nouveaux</div>
                                        </div>
                                        <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                                    <div class="w-4 h-4 bg-orange-500 rounded"></div>
                                                </div>
                                                <span class="text-xs text-slate-500">Reserv.</span>
                                            </div>
                                            <div class="text-xl sm:text-2xl font-bold text-slate-900">12</div>
                                            <div class="text-xs text-orange-600">En attente</div>
                                        </div>
                                    </div>
                                    <!-- Chart Area -->
                                    <div class="grid md:grid-cols-3 gap-4">
                                        <div class="md:col-span-2 bg-white rounded-xl p-4 shadow-sm">
                                            <div class="flex justify-between items-center mb-4">
                                                <div class="h-5 w-32 bg-slate-200 rounded"></div>
                                                <div class="flex gap-2">
                                                    <div class="h-6 w-16 bg-slate-100 rounded"></div>
                                                    <div class="h-6 w-16 bg-indigo-100 rounded"></div>
                                                </div>
                                            </div>
                                            <!-- Chart bars -->
                                            <div class="flex items-end gap-2 h-32">
                                                <div class="flex-1 bg-indigo-200 rounded-t" style="height: 60%"></div>
                                                <div class="flex-1 bg-indigo-300 rounded-t" style="height: 75%"></div>
                                                <div class="flex-1 bg-indigo-400 rounded-t" style="height: 55%"></div>
                                                <div class="flex-1 bg-indigo-500 rounded-t" style="height: 85%"></div>
                                                <div class="flex-1 bg-indigo-600 rounded-t" style="height: 92%"></div>
                                                <div class="flex-1 bg-indigo-500 rounded-t" style="height: 78%"></div>
                                                <div class="flex-1 bg-indigo-400 rounded-t" style="height: 65%"></div>
                                                <div class="flex-1 bg-indigo-300 rounded-t hidden sm:block" style="height: 70%"></div>
                                                <div class="flex-1 bg-indigo-400 rounded-t hidden sm:block" style="height: 82%"></div>
                                                <div class="flex-1 bg-indigo-500 rounded-t hidden sm:block" style="height: 88%"></div>
                                                <div class="flex-1 bg-indigo-600 rounded-t hidden sm:block" style="height: 95%"></div>
                                                <div class="flex-1 bg-purple-500 rounded-t hidden sm:block" style="height: 100%"></div>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-xl p-4 shadow-sm">
                                            <div class="h-5 w-24 bg-slate-200 rounded mb-4"></div>
                                            <div class="space-y-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="h-3 w-20 bg-slate-200 rounded mb-1"></div>
                                                        <div class="h-2 w-16 bg-slate-100 rounded"></div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="h-3 w-24 bg-slate-200 rounded mb-1"></div>
                                                        <div class="h-2 w-12 bg-slate-100 rounded"></div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="h-3 w-28 bg-slate-200 rounded mb-1"></div>
                                                        <div class="h-2 w-14 bg-slate-100 rounded"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Notifications -->
                    <div class="absolute -left-4 top-1/3 bg-white rounded-xl shadow-2xl p-4 hidden lg:flex items-center gap-3 animate-float z-20">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Nouveau contrat</p>
                            <p class="text-sm text-slate-500">Box A-12 - 89 EUR/mois</p>
                        </div>
                    </div>

                    <div class="absolute -right-4 top-1/2 bg-white rounded-xl shadow-2xl p-4 hidden lg:flex items-center gap-3 animate-float animation-delay-2000 z-20">
                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">Paiement recu</p>
                            <p class="text-sm text-slate-500">+1,250.00 EUR - Stripe</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-20 bg-white border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-5xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                            {{ formatNumber(stats.total_sites) }}+
                        </div>
                        <p class="text-slate-600 font-medium">Sites geres</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                            {{ formatNumber(stats.total_boxes) }}+
                        </div>
                        <p class="text-slate-600 font-medium">Boxes actives</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                            {{ formatNumber(stats.total_customers) }}+
                        </div>
                        <p class="text-slate-600 font-medium">Clients satisfaits</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
                            {{ stats.average_rating }}/5
                        </div>
                        <p class="text-slate-600 font-medium">Note moyenne</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Block 1: Plan Interactif -->
        <section class="py-24 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- Text -->
                    <div>
                        <span class="px-4 py-1.5 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium mb-4 inline-block">
                            Plan Interactif
                        </span>
                        <h2 class="text-4xl font-bold text-slate-900 mb-6">
                            Visualisez vos boxes en temps reel
                        </h2>
                        <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                            Notre plan interactif vous permet de voir l'etat de chaque box en un coup d'oeil.
                            Cliquez sur une box pour voir ses details, son client, son contrat et son historique.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Code couleur</strong> - Vert (libre), Rouge (occupe), Orange (reserve)</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Drag & Drop</strong> - Deplacez les boxes sur le plan facilement</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Multi-etages</strong> - Gerez plusieurs niveaux de batiment</span>
                            </li>
                        </ul>
                    </div>
                    <!-- Plan Interactif Screenshot -->
                    <div class="relative">
                        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
                            <!-- Browser Frame -->
                            <div class="bg-slate-800 p-3 flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                </div>
                                <div class="flex-1 text-center">
                                    <div class="inline-flex items-center gap-2 px-4 py-1 bg-slate-700/50 rounded-lg text-slate-400 text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        app.boxibox.fr/tenant/plan/interactive
                                    </div>
                                </div>
                            </div>
                            <!-- Screenshot Image -->
                            <div class="relative">
                                <img
                                    v-if="!planScreenshotError"
                                    src="/images/screenshots/plan-pro.png"
                                    alt="Plan Interactif Pro - Visualisation en temps reel"
                                    class="w-full h-auto"
                                    @error="planScreenshotError = true"
                                />
                                <!-- Fallback Mockup if image not found -->
                                <div v-else class="p-4 bg-slate-50">
                                    <!-- Floor tabs -->
                                    <div class="flex gap-2 mb-4">
                                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">RDC</button>
                                        <button class="px-4 py-2 bg-white text-slate-600 rounded-lg text-sm font-medium border">Etage 1</button>
                                        <button class="px-4 py-2 bg-white text-slate-600 rounded-lg text-sm font-medium border">Etage 2</button>
                                    </div>
                                    <!-- Grid of boxes -->
                                    <div class="bg-white rounded-xl p-4 border-2 border-dashed border-slate-200">
                                        <div class="grid grid-cols-8 gap-2">
                                            <div class="aspect-square bg-green-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A1</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A2</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A3</div>
                                            <div class="aspect-square bg-green-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A4</div>
                                            <div class="aspect-square bg-orange-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A5</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A6</div>
                                            <div class="aspect-square bg-green-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A7</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm hover:scale-105 transition cursor-pointer">A8</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B1</div>
                                            <div class="aspect-square bg-green-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B2</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B3</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B4</div>
                                            <div class="aspect-square bg-green-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B5</div>
                                            <div class="aspect-square bg-orange-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B6</div>
                                            <div class="aspect-square bg-red-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B7</div>
                                            <div class="aspect-square bg-green-500 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">B8</div>
                                            <div class="col-span-2 aspect-[2/1] bg-red-500 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-sm">C1-2</div>
                                            <div class="col-span-2 aspect-[2/1] bg-green-500 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-sm">C3-4</div>
                                            <div class="col-span-2 aspect-[2/1] bg-red-500 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-sm">C5-6</div>
                                            <div class="col-span-2 aspect-[2/1] bg-orange-500 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-sm">C7-8</div>
                                        </div>
                                    </div>
                                    <!-- Legend -->
                                    <div class="flex gap-6 mt-4 justify-center text-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="w-4 h-4 bg-green-500 rounded"></div>
                                            <span class="text-slate-600">Libre (8)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-4 h-4 bg-red-500 rounded"></div>
                                            <span class="text-slate-600">Occupe (12)</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-4 h-4 bg-orange-500 rounded"></div>
                                            <span class="text-slate-600">Reserve (4)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -z-10 -bottom-6 -right-6 w-full h-full bg-gradient-to-br from-indigo-200 to-purple-200 rounded-2xl"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Block 2: CRM & Clients (Reversed) -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- CRM Mockup (Left on desktop) -->
                    <div class="relative order-2 lg:order-1">
                        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
                            <div class="bg-slate-800 p-3 flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                </div>
                                <div class="text-slate-400 text-sm ml-4">Clients - CRM</div>
                            </div>
                            <div class="p-4 bg-white">
                                <!-- Search and filters -->
                                <div class="flex gap-3 mb-4">
                                    <div class="flex-1 bg-slate-100 rounded-lg px-4 py-2 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        <span class="text-slate-400 text-sm">Rechercher un client...</span>
                                    </div>
                                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">+ Ajouter</button>
                                </div>
                                <!-- Client list -->
                                <div class="space-y-3">
                                    <div class="flex items-center gap-4 p-3 bg-indigo-50 rounded-xl border-2 border-indigo-200">
                                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">JD</div>
                                        <div class="flex-1">
                                            <div class="font-semibold text-slate-900">Jean Dupont</div>
                                            <div class="text-sm text-slate-500">jean.dupont@email.fr - Box A-12</div>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Actif</span>
                                            <div class="text-sm text-slate-500 mt-1">89 EUR/mois</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 bg-white rounded-xl border border-slate-200">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold">ML</div>
                                        <div class="flex-1">
                                            <div class="font-semibold text-slate-900">Marie Leroy</div>
                                            <div class="text-sm text-slate-500">m.leroy@gmail.com - Box B-05</div>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Actif</span>
                                            <div class="text-sm text-slate-500 mt-1">129 EUR/mois</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-3 bg-white rounded-xl border border-slate-200">
                                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full flex items-center justify-center text-white font-bold">PM</div>
                                        <div class="flex-1">
                                            <div class="font-semibold text-slate-900">Pierre Martin</div>
                                            <div class="text-sm text-slate-500">pierre.m@company.fr - Box C-18</div>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">En retard</span>
                                            <div class="text-sm text-slate-500 mt-1">249 EUR/mois</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pagination -->
                                <div class="flex justify-between items-center mt-4 pt-4 border-t">
                                    <span class="text-sm text-slate-500">324 clients au total</span>
                                    <div class="flex gap-1">
                                        <button class="w-8 h-8 rounded bg-indigo-600 text-white text-sm">1</button>
                                        <button class="w-8 h-8 rounded bg-slate-100 text-slate-600 text-sm">2</button>
                                        <button class="w-8 h-8 rounded bg-slate-100 text-slate-600 text-sm">3</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -z-10 -bottom-6 -left-6 w-full h-full bg-gradient-to-br from-green-200 to-emerald-200 rounded-2xl"></div>
                    </div>
                    <!-- Text (Right on desktop) -->
                    <div class="order-1 lg:order-2">
                        <span class="px-4 py-1.5 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-4 inline-block">
                            CRM Integre
                        </span>
                        <h2 class="text-4xl font-bold text-slate-900 mb-6">
                            Gerez vos clients efficacement
                        </h2>
                        <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                            Centralisez toutes les informations de vos clients: coordonnees, contrats actifs,
                            historique des paiements, documents et communications.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Fiche client complete</strong> - Toutes les infos en un clic</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Historique complet</strong> - Contrats, factures, communications</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Portail client</strong> - Espace personnel pour vos locataires</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Block 3: Contrats & Facturation -->
        <section class="py-24 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- Text -->
                    <div>
                        <span class="px-4 py-1.5 bg-purple-100 text-purple-700 rounded-full text-sm font-medium mb-4 inline-block">
                            Contrats & Facturation
                        </span>
                        <h2 class="text-4xl font-bold text-slate-900 mb-6">
                            Automatisez votre facturation
                        </h2>
                        <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                            Creez des contrats en quelques clics, generez automatiquement les factures
                            et recevez les paiements par prelevement SEPA ou carte bancaire.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Signature electronique</strong> - Contrats signes en ligne</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Prelevement automatique</strong> - SEPA et Carte via Stripe/GoCardless</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Relances automatiques</strong> - Email et SMS pour les impayes</span>
                            </li>
                        </ul>
                    </div>
                    <!-- Contract Mockup -->
                    <div class="relative">
                        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
                            <div class="bg-slate-800 p-3 flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                </div>
                                <div class="text-slate-400 text-sm ml-4">Nouveau Contrat</div>
                            </div>
                            <div class="p-6 bg-white">
                                <!-- Contract Form -->
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg text-slate-900">Contrat #2024-0156</h3>
                                        <p class="text-slate-500 text-sm">Jean Dupont - Box A-12</p>
                                    </div>
                                    <span class="ml-auto px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Actif</span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="bg-slate-50 rounded-xl p-4">
                                        <p class="text-xs text-slate-500 mb-1">Date de debut</p>
                                        <p class="font-semibold text-slate-900">15 Janvier 2024</p>
                                    </div>
                                    <div class="bg-slate-50 rounded-xl p-4">
                                        <p class="text-xs text-slate-500 mb-1">Prochaine facture</p>
                                        <p class="font-semibold text-slate-900">15 Fevrier 2024</p>
                                    </div>
                                </div>

                                <!-- Invoice Preview -->
                                <div class="border border-slate-200 rounded-xl p-4">
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="font-medium text-slate-900">Facture en cours</span>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">En attente</span>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Location Box A-12 (6m2)</span>
                                            <span class="text-slate-900">89.00 EUR</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-slate-600">Assurance Premium</span>
                                            <span class="text-slate-900">15.00 EUR</span>
                                        </div>
                                        <div class="flex justify-between pt-2 border-t font-semibold">
                                            <span class="text-slate-900">Total TTC</span>
                                            <span class="text-indigo-600">104.00 EUR</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <button class="flex-1 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">Envoyer la facture</button>
                                    <button class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-700">PDF</button>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -z-10 -bottom-6 -right-6 w-full h-full bg-gradient-to-br from-purple-200 to-pink-200 rounded-2xl"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Block 4: Reservations en ligne (Reversed) -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- Reservation Mockup -->
                    <div class="relative order-2 lg:order-1">
                        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
                            <div class="bg-gradient-to-r from-orange-500 to-amber-500 p-4">
                                <h3 class="text-white font-bold text-lg">Reservez votre box en ligne</h3>
                                <p class="text-orange-100 text-sm">24h/24 - 7j/7</p>
                            </div>
                            <div class="p-6 bg-white">
                                <!-- Step indicator -->
                                <div class="flex items-center gap-2 mb-6">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-bold">1</div>
                                    <div class="flex-1 h-1 bg-green-500 rounded"></div>
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-bold">2</div>
                                    <div class="flex-1 h-1 bg-green-500 rounded"></div>
                                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-white text-sm font-bold">3</div>
                                    <div class="flex-1 h-1 bg-slate-200 rounded"></div>
                                    <div class="w-8 h-8 bg-slate-200 rounded-full flex items-center justify-center text-slate-400 text-sm font-bold">4</div>
                                </div>

                                <!-- Box selection -->
                                <h4 class="font-semibold text-slate-900 mb-4">Choisissez votre box</h4>
                                <div class="grid grid-cols-2 gap-3 mb-6">
                                    <div class="border border-slate-200 rounded-xl p-4 hover:border-orange-500 cursor-pointer transition">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-900">3m2</p>
                                                <p class="text-sm text-slate-500">Petit</p>
                                            </div>
                                        </div>
                                        <p class="text-right mt-2 font-bold text-orange-600">49 EUR/mois</p>
                                    </div>
                                    <div class="border-2 border-orange-500 rounded-xl p-4 bg-orange-50 cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 bg-orange-200 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-900">6m2</p>
                                                <p class="text-sm text-slate-500">Moyen</p>
                                            </div>
                                        </div>
                                        <p class="text-right mt-2 font-bold text-orange-600">89 EUR/mois</p>
                                    </div>
                                </div>

                                <!-- Calendar -->
                                <h4 class="font-semibold text-slate-900 mb-3">Date d'entree</h4>
                                <div class="bg-slate-50 rounded-xl p-3 mb-4">
                                    <div class="flex justify-between items-center mb-3">
                                        <button class="text-slate-400">&lt;</button>
                                        <span class="font-medium text-slate-900">Janvier 2024</span>
                                        <button class="text-slate-400">&gt;</button>
                                    </div>
                                    <div class="grid grid-cols-7 gap-1 text-center text-xs">
                                        <span class="text-slate-400 py-1">L</span>
                                        <span class="text-slate-400 py-1">M</span>
                                        <span class="text-slate-400 py-1">M</span>
                                        <span class="text-slate-400 py-1">J</span>
                                        <span class="text-slate-400 py-1">V</span>
                                        <span class="text-slate-400 py-1">S</span>
                                        <span class="text-slate-400 py-1">D</span>
                                        <span class="py-1 text-slate-300">29</span>
                                        <span class="py-1 text-slate-300">30</span>
                                        <span class="py-1 text-slate-300">31</span>
                                        <span class="py-1">1</span>
                                        <span class="py-1">2</span>
                                        <span class="py-1">3</span>
                                        <span class="py-1">4</span>
                                        <span class="py-1">5</span>
                                        <span class="py-1">6</span>
                                        <span class="py-1">7</span>
                                        <span class="py-1">8</span>
                                        <span class="py-1">9</span>
                                        <span class="py-1">10</span>
                                        <span class="py-1">11</span>
                                        <span class="py-1">12</span>
                                        <span class="py-1">13</span>
                                        <span class="py-1">14</span>
                                        <span class="py-1 bg-orange-500 text-white rounded-full">15</span>
                                        <span class="py-1">16</span>
                                        <span class="py-1">17</span>
                                        <span class="py-1">18</span>
                                    </div>
                                </div>

                                <button class="w-full py-3 bg-gradient-to-r from-orange-500 to-amber-500 text-white rounded-xl font-semibold">
                                    Continuer vers le paiement
                                </button>
                            </div>
                        </div>
                        <div class="absolute -z-10 -bottom-6 -left-6 w-full h-full bg-gradient-to-br from-orange-200 to-amber-200 rounded-2xl"></div>
                    </div>
                    <!-- Text -->
                    <div class="order-1 lg:order-2">
                        <span class="px-4 py-1.5 bg-orange-100 text-orange-700 rounded-full text-sm font-medium mb-4 inline-block">
                            Reservations 24/7
                        </span>
                        <h2 class="text-4xl font-bold text-slate-900 mb-6">
                            Vos clients reservent en ligne
                        </h2>
                        <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                            Offrez a vos clients la possibilite de reserver et payer leur box 24h/24,
                            7j/7, directement sur votre site web. Plus de clients, moins de travail.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Widget integrable</strong> - Sur votre site en 5 minutes</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Paiement en ligne</strong> - Stripe, PayPal, carte bancaire</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Contrat automatique</strong> - Genere et signe en ligne</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Block 5: IoT & Serrures -->
        <section class="py-24 bg-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- Text -->
                    <div>
                        <span class="px-4 py-1.5 bg-cyan-500/20 text-cyan-400 rounded-full text-sm font-medium mb-4 inline-block">
                            IoT & Serrures Connectees
                        </span>
                        <h2 class="text-4xl font-bold text-white mb-6">
                            Controlez les acces a distance
                        </h2>
                        <p class="text-lg text-slate-300 mb-8 leading-relaxed">
                            Integrez vos serrures connectees (Noke, Salto, Kisi, PTI) et gerez les acces
                            de vos clients a distance. Ouvrez les portes depuis votre smartphone.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-cyan-500/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-300"><strong class="text-white">Serrures connectees</strong> - Noke, Salto KS, Kisi, PTI</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-cyan-500/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-300"><strong class="text-white">Capteurs IoT</strong> - Temperature, humidite, intrusion</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-cyan-500/20 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-300"><strong class="text-white">Historique d'acces</strong> - Qui a ouvert quoi et quand</span>
                            </li>
                        </ul>
                    </div>
                    <!-- IoT Mockup -->
                    <div class="relative">
                        <div class="bg-slate-800 rounded-2xl shadow-2xl overflow-hidden border border-slate-700">
                            <div class="bg-slate-900 p-3 flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                </div>
                                <div class="text-slate-400 text-sm ml-4">IoT - Serrures Connectees</div>
                            </div>
                            <div class="p-6">
                                <!-- Lock status cards -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="bg-slate-700/50 rounded-xl p-4 border border-slate-600">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-white">Box A-12</p>
                                                <p class="text-xs text-slate-400">Noke Pro</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-green-400">Deverrouille</span>
                                            <div class="flex items-center gap-1 text-xs text-slate-400">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                                                85%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-slate-700/50 rounded-xl p-4 border border-slate-600">
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-10 h-10 bg-cyan-500/20 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-white">Box B-05</p>
                                                <p class="text-xs text-slate-400">Salto KS</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-cyan-400">Verrouille</span>
                                            <div class="flex items-center gap-1 text-xs text-slate-400">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                                                92%
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Access history -->
                                <h4 class="text-sm font-medium text-slate-400 mb-3">Historique d'acces</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 p-3 bg-slate-700/30 rounded-lg">
                                        <div class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-white text-sm">Box A-12 deverrouille</p>
                                            <p class="text-xs text-slate-400">Jean Dupont - il y a 5 min</p>
                                        </div>
                                        <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs">Succes</span>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-slate-700/30 rounded-lg">
                                        <div class="w-8 h-8 bg-cyan-500/20 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-white text-sm">Box B-05 verrouille</p>
                                            <p class="text-xs text-slate-400">Marie Leroy - il y a 12 min</p>
                                        </div>
                                        <span class="px-2 py-1 bg-cyan-500/20 text-cyan-400 rounded text-xs">Succes</span>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-slate-700/30 rounded-lg">
                                        <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-white text-sm">Tentative d'acces refusee</p>
                                            <p class="text-xs text-slate-400">Box C-08 - il y a 1h</p>
                                        </div>
                                        <span class="px-2 py-1 bg-red-500/20 text-red-400 rounded text-xs">Echec</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -z-10 -bottom-6 -right-6 w-full h-full bg-gradient-to-br from-cyan-900 to-slate-800 rounded-2xl"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Block 6: Analytics -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- Analytics Mockup -->
                    <div class="relative order-2 lg:order-1">
                        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
                            <div class="bg-slate-800 p-3 flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                </div>
                                <div class="text-slate-400 text-sm ml-4">Analytics - Tableau de bord</div>
                            </div>
                            <div class="p-6 bg-slate-50">
                                <!-- KPI Cards -->
                                <div class="grid grid-cols-3 gap-3 mb-6">
                                    <div class="bg-white rounded-xl p-4 shadow-sm">
                                        <p class="text-xs text-slate-500 mb-1">Taux d'occupation</p>
                                        <p class="text-2xl font-bold text-slate-900">87.3%</p>
                                        <div class="flex items-center gap-1 mt-1">
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                            <span class="text-xs text-green-600">+5.2%</span>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 shadow-sm">
                                        <p class="text-xs text-slate-500 mb-1">CA Mensuel</p>
                                        <p class="text-2xl font-bold text-slate-900">45.2k EUR</p>
                                        <div class="flex items-center gap-1 mt-1">
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                            <span class="text-xs text-green-600">+12.4%</span>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 shadow-sm">
                                        <p class="text-xs text-slate-500 mb-1">Duree moyenne</p>
                                        <p class="text-2xl font-bold text-slate-900">8.5 mois</p>
                                        <div class="flex items-center gap-1 mt-1">
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                            <span class="text-xs text-green-600">+0.8</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Revenue Chart -->
                                <div class="bg-white rounded-xl p-4 shadow-sm mb-4">
                                    <div class="flex justify-between items-center mb-4">
                                        <h4 class="font-medium text-slate-900">Evolution des revenus</h4>
                                        <div class="flex gap-2">
                                            <button class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Annee</button>
                                            <button class="px-3 py-1 text-xs bg-slate-100 text-slate-600 rounded-full">Mois</button>
                                        </div>
                                    </div>
                                    <!-- Line chart mockup -->
                                    <div class="h-32 flex items-end gap-1">
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 40%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Jan</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 45%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Fev</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 52%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Mar</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 48%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Avr</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 60%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Mai</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 68%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Jun</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 75%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Jul</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 72%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Aou</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 80%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Sep</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 85%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Oct</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t" style="height: 90%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Nov</span>
                                        </div>
                                        <div class="flex-1 flex flex-col items-center">
                                            <div class="w-full bg-gradient-to-t from-indigo-600 to-purple-500 rounded-t" style="height: 100%"></div>
                                            <span class="text-xs text-slate-400 mt-1">Dec</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Occupancy by site -->
                                <div class="bg-white rounded-xl p-4 shadow-sm">
                                    <h4 class="font-medium text-slate-900 mb-3">Occupation par site</h4>
                                    <div class="space-y-3">
                                        <div>
                                            <div class="flex justify-between text-sm mb-1">
                                                <span class="text-slate-600">Paris - Centre</span>
                                                <span class="font-medium text-slate-900">92%</span>
                                            </div>
                                            <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-green-500 rounded-full" style="width: 92%"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-sm mb-1">
                                                <span class="text-slate-600">Lyon - Part-Dieu</span>
                                                <span class="font-medium text-slate-900">87%</span>
                                            </div>
                                            <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-green-500 rounded-full" style="width: 87%"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-sm mb-1">
                                                <span class="text-slate-600">Marseille - Prado</span>
                                                <span class="font-medium text-slate-900">78%</span>
                                            </div>
                                            <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                                <div class="h-full bg-yellow-500 rounded-full" style="width: 78%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -z-10 -bottom-6 -left-6 w-full h-full bg-gradient-to-br from-blue-200 to-indigo-200 rounded-2xl"></div>
                    </div>
                    <!-- Text -->
                    <div class="order-1 lg:order-2">
                        <span class="px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-medium mb-4 inline-block">
                            Analytics Avances
                        </span>
                        <h2 class="text-4xl font-bold text-slate-900 mb-6">
                            Analysez vos performances
                        </h2>
                        <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                            Tableaux de bord detailles, KPIs en temps reel, rapports personnalises.
                            Prenez les bonnes decisions grace a des donnees fiables.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Taux d'occupation</strong> - Suivi en temps reel par site</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Revenus et CA</strong> - Graphiques et tendances</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="text-slate-700"><strong>Export Excel/PDF</strong> - Rapports personnalises</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="py-24 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="px-4 py-1.5 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium mb-4 inline-block">
                        Temoignages
                    </span>
                    <h2 class="text-4xl font-bold text-slate-900 mb-4">
                        Ce que disent nos clients
                    </h2>
                    <p class="text-lg text-slate-600">
                        Rejoignez les operateurs qui nous font confiance
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="(testimonial, index) in testimonials" :key="index"
                         class="bg-white rounded-2xl p-8 shadow-lg border border-slate-100">
                        <div class="flex gap-1 mb-6">
                            <svg v-for="i in testimonial.rating" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <blockquote class="text-slate-700 mb-6 leading-relaxed">
                            "{{ testimonial.quote }}"
                        </blockquote>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ testimonial.name.charAt(0) }}
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">{{ testimonial.name }}</p>
                                <p class="text-slate-500 text-sm">{{ testimonial.company }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Preview -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="px-4 py-1.5 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-4 inline-block">
                        Tarifs
                    </span>
                    <h2 class="text-4xl font-bold text-slate-900 mb-4">
                        Des tarifs simples et transparents
                    </h2>
                    <p class="text-lg text-slate-600">
                        Choisissez le plan adapte a votre croissance
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <div v-for="(plan, index) in pricing" :key="index"
                         :class="[
                             'relative rounded-2xl p-8',
                             plan.popular
                                 ? 'bg-gradient-to-b from-indigo-600 to-purple-700 text-white ring-4 ring-indigo-500/50 scale-105 shadow-2xl'
                                 : 'bg-white border-2 border-slate-200'
                         ]">
                        <div v-if="plan.popular" class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="bg-gradient-to-r from-yellow-400 to-orange-500 text-slate-900 text-sm font-bold px-4 py-1 rounded-full">
                                Le plus populaire
                            </span>
                        </div>

                        <h3 :class="['text-xl font-bold mb-2', plan.popular ? 'text-white' : 'text-slate-900']">
                            {{ plan.name }}
                        </h3>
                        <p :class="['text-sm mb-6', plan.popular ? 'text-indigo-200' : 'text-slate-500']">
                            {{ plan.description }}
                        </p>

                        <div class="mb-6">
                            <span :class="['text-5xl font-bold', plan.popular ? 'text-white' : 'text-slate-900']">
                                {{ plan.price }} EUR
                            </span>
                            <span :class="plan.popular ? 'text-indigo-200' : 'text-slate-500'">/mois</span>
                        </div>

                        <ul class="space-y-3 mb-8">
                            <li v-for="(feature, fIndex) in plan.features" :key="fIndex" class="flex items-start gap-3">
                                <svg :class="['w-5 h-5 mt-0.5', plan.popular ? 'text-indigo-300' : 'text-green-500']" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span :class="plan.popular ? 'text-indigo-100' : 'text-slate-600'">{{ feature }}</span>
                            </li>
                        </ul>

                        <Link :href="plan.popular ? '/demo' : '/pricing'"
                              :class="[
                                  'block w-full py-3 px-4 rounded-xl font-semibold text-center transition-all',
                                  plan.popular ? 'bg-white text-indigo-600 hover:bg-indigo-50' : 'bg-slate-900 text-white hover:bg-slate-800'
                              ]">
                            {{ plan.cta }}
                        </Link>
                    </div>
                </div>

                <div class="text-center mt-12">
                    <Link href="/pricing" class="text-indigo-600 font-semibold hover:text-indigo-700 inline-flex items-center gap-2">
                        Voir tous les tarifs et comparer
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800 relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-[url('/images/grid.svg')] opacity-10"></div>
            </div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6">
                    Pret a transformer votre self-storage ?
                </h2>
                <p class="text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
                    Rejoignez les operateurs qui ont augmente leurs revenus de 30% en moyenne avec Boxibox.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link href="/demo" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold bg-white text-indigo-600 rounded-xl hover:bg-indigo-50 transition-all shadow-xl">
                        Demander une demo gratuite
                    </Link>
                    <Link href="/calculator" class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white border-2 border-white/30 rounded-xl hover:bg-white/10 transition-all">
                        Calculer mes economies
                    </Link>
                </div>
                <p class="mt-8 text-indigo-200 text-sm">
                    14 jours d'essai gratuit - Sans carte bancaire - Configuration en 5 minutes
                </p>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

// Screenshot error states - fallback to mockup if image not found
const planScreenshotError = ref(false)

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_sites: 150,
            total_boxes: 8500,
            total_customers: 12000,
            average_rating: 4.8
        })
    },
    testimonials: {
        type: Array,
        default: () => [
            { rating: 5, quote: 'Boxibox a transforme notre facon de travailler. On gagne 15h par semaine et notre taux d\'occupation a augmente de 23%.', name: 'Marie Dupont', company: 'StoragePlus Paris' },
            { rating: 5, quote: 'L\'interface est intuitive et le support est exceptionnel. Je recommande a 100% pour tout operateur de self-storage.', name: 'Jean Martin', company: 'Box&Co Lyon' },
            { rating: 5, quote: 'Depuis qu\'on utilise Boxibox, nos clients peuvent reserver en ligne 24h/24. C\'est un vrai game changer!', name: 'Sophie Bernard', company: 'SecureBox Marseille' },
        ]
    },
    pricing: {
        type: Array,
        default: () => [
            { name: 'Starter', description: 'Pour demarrer', price: '49', features: ['Jusqu\'a 50 boxes', '1 site', '2 utilisateurs', 'Support email'], cta: 'Voir les tarifs', popular: false },
            { name: 'Professional', description: 'Pour les pros', price: '149', features: ['Jusqu\'a 200 boxes', '3 sites', '10 utilisateurs', 'Reservations en ligne', 'Support prioritaire'], cta: 'Essai gratuit 14 jours', popular: true },
            { name: 'Business', description: 'Pour les groupes', price: '349', features: ['Boxes illimitees', 'Sites illimites', 'Utilisateurs illimites', 'API complete', 'Account manager'], cta: 'Contacter les ventes', popular: false },
        ]
    },
})

const formatNumber = (num) => {
    if (!num) return '0'
    if (num >= 1000) {
        return (num / 1000).toFixed(1).replace('.0', '') + 'k'
    }
    return num.toString()
}
</script>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}
</style>
