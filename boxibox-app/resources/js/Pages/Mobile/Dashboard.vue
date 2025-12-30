<template>
    <MobileLayout>
        <!-- Install App Banner -->
        <Transition
            enter-active-class="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            enter-from-class="opacity-0 -translate-y-6 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-4 scale-95"
        >
            <div v-if="showInstallBanner" class="mb-5">
                <div class="group relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 rounded-[1.75rem] p-5 text-white shadow-2xl shadow-purple-500/25 hover:shadow-purple-500/40 transition-all duration-500">
                    <!-- Animated background decoration -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl animate-pulse-slow"></div>
                    <div class="absolute bottom-0 left-1/4 w-24 h-24 bg-pink-400/20 rounded-full translate-y-1/2 blur-xl animate-float"></div>

                    <div class="relative flex items-center">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mr-4 flex-shrink-0 ring-2 ring-white/20 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                            <DevicePhoneMobileIcon class="w-7 h-7" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-[15px] tracking-tight">Installer l'application</h4>
                            <p class="text-purple-100 text-[13px] mt-0.5 leading-snug">Acces rapide depuis votre ecran d'accueil</p>
                        </div>
                        <div class="flex items-center space-x-2 ml-3">
                            <button
                                @click="installApp"
                                class="px-5 py-2.5 bg-white text-purple-600 rounded-2xl font-bold text-sm hover:bg-purple-50 active:scale-95 transition-all duration-200 shadow-lg shadow-purple-900/20"
                            >
                                Installer
                            </button>
                            <button
                                @click="dismissInstallBanner"
                                class="p-2.5 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 active:scale-90 transition-all duration-200"
                            >
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- iOS Install Instructions -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showIOSInstructions" class="fixed inset-0 z-[200] flex items-end justify-center">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showIOSInstructions = false"></div>
                <Transition
                    enter-active-class="transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-all duration-300 ease-in"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div v-if="showIOSInstructions" class="relative bg-white dark:bg-gray-800 w-full rounded-t-[2rem] p-6 pb-10 shadow-2xl">
                        <div class="flex justify-center mb-5">
                            <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-5 text-center tracking-tight">Installer sur iPhone/iPad</h3>
                        <div class="space-y-5">
                            <div class="flex items-start group">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 flex-shrink-0 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-white font-bold">1</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">Appuyez sur le bouton Partager</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">Le carre avec la fleche vers le haut en bas de Safari</p>
                                </div>
                            </div>
                            <div class="flex items-start group">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 flex-shrink-0 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-white font-bold">2</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">Selectionnez "Sur l'ecran d'accueil"</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">Faites defiler vers le bas si necessaire</p>
                                </div>
                            </div>
                            <div class="flex items-start group">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 flex-shrink-0 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform duration-300">
                                    <span class="text-white font-bold">3</span>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">Appuyez sur "Ajouter"</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">L'application sera ajoutee a votre ecran</p>
                                </div>
                            </div>
                        </div>
                        <button
                            @click="showIOSInstructions = false"
                            class="w-full mt-7 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-2xl font-bold text-base shadow-lg shadow-primary-500/30 active:scale-[0.98] transition-all duration-200"
                        >
                            Compris
                        </button>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- Welcome Banner with Premium Glassmorphism -->
        <div class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 rounded-[1.75rem] p-6 text-white mb-7 shadow-2xl shadow-primary-600/40 group">
            <!-- Animated background decorations -->
            <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl animate-pulse-slow"></div>
            <div class="absolute bottom-0 left-0 w-40 h-40 bg-primary-400/20 rounded-full translate-y-1/2 -translate-x-1/2 blur-2xl animate-float"></div>
            <div class="absolute top-1/2 right-1/4 w-20 h-20 bg-indigo-400/20 rounded-full blur-xl animate-float-delayed"></div>

            <div class="relative">
                <div class="flex items-center justify-between mb-7">
                    <div>
                        <p class="text-primary-200/90 text-sm font-medium tracking-wide">{{ greeting }},</p>
                        <h2 class="text-[1.65rem] font-bold mt-1 tracking-tight leading-tight">{{ customerName }}</h2>
                    </div>
                    <div class="w-[4.5rem] h-[4.5rem] bg-white/20 backdrop-blur-xl rounded-[1.25rem] flex items-center justify-center ring-2 ring-white/30 shadow-xl group-hover:scale-105 group-hover:rotate-2 transition-all duration-500">
                        <span class="text-[1.75rem] font-bold">{{ userInitial }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/15 backdrop-blur-md rounded-2xl p-4 border border-white/10 hover:bg-white/20 transition-all duration-300 group/card">
                        <p class="text-primary-200/80 text-xs font-semibold mb-1.5 tracking-wide uppercase">Solde en cours</p>
                        <p class="text-[1.65rem] font-bold tracking-tight group-hover/card:scale-105 transition-transform duration-300 origin-left">{{ formatCurrency(stats.outstanding_balance) }}</p>
                    </div>
                    <div class="bg-white/15 backdrop-blur-md rounded-2xl p-4 border border-white/10 hover:bg-white/20 transition-all duration-300 group/card">
                        <p class="text-primary-200/80 text-xs font-semibold mb-1.5 tracking-wide uppercase">Prochain paiement</p>
                        <p class="text-[1.65rem] font-bold tracking-tight group-hover/card:scale-105 transition-transform duration-300 origin-left">{{ nextPaymentDate }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weather & Notifications Quick Row -->
        <div class="grid grid-cols-2 gap-4 mb-7">
            <!-- Weather Widget -->
            <div class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-4 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1 active:scale-[0.98] transition-all duration-300 group">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Meteo</p>
                        <p class="text-[1.75rem] font-bold text-gray-900 dark:text-white mt-0.5 tracking-tight">{{ weather.temp }}°</p>
                    </div>
                    <div class="text-4xl group-hover:scale-125 group-hover:rotate-12 transition-all duration-500">{{ weather.icon }}</div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 font-medium">{{ weather.description }}</p>
            </div>

            <!-- Notifications Widget -->
            <Link
                :href="route('mobile.notifications')"
                class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-4 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1 active:scale-[0.98] transition-all duration-300 group"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider">Notifications</p>
                        <p class="text-[1.75rem] font-bold text-gray-900 dark:text-white mt-0.5 tracking-tight">{{ unreadNotifications }}</p>
                    </div>
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900/50 dark:to-primary-800/50 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <BellIcon class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                        </div>
                        <span v-if="unreadNotifications > 0" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-gradient-to-br from-red-500 to-rose-600 rounded-full flex items-center justify-center shadow-lg shadow-red-500/40 animate-pulse">
                            <span class="text-[10px] text-white font-bold">{{ unreadNotifications > 9 ? '9+' : unreadNotifications }}</span>
                        </span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 font-medium">non lues</p>
            </Link>
        </div>

        <!-- Promo Banner (if any) -->
        <Transition
            enter-active-class="transition-all duration-600 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            enter-from-class="opacity-0 -translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
        >
            <div v-if="promoBanner" class="mb-7">
                <div class="group relative overflow-hidden bg-gradient-to-r from-violet-500 via-purple-500 to-fuchsia-500 rounded-[1.75rem] p-5 text-white shadow-2xl shadow-purple-500/30 hover:shadow-purple-500/50 active:scale-[0.98] transition-all duration-300 cursor-pointer">
                    <!-- Animated decorations -->
                    <div class="absolute top-0 right-0 w-36 h-36 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-xl animate-pulse-slow"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-fuchsia-400/20 rounded-full translate-y-1/2 blur-lg animate-float"></div>

                    <div class="relative flex items-center">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mr-4 flex-shrink-0 ring-2 ring-white/20 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                            <GiftIcon class="w-7 h-7" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-[15px] tracking-tight">{{ promoBanner.title }}</h4>
                            <p class="text-purple-100 text-[13px] mt-0.5 leading-snug">{{ promoBanner.description }}</p>
                        </div>
                        <div class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center group-hover:bg-white/20 group-hover:translate-x-1 transition-all duration-300">
                            <ChevronRightIcon class="w-5 h-5 text-white/80" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Quick Stats with Modern Cards -->
        <div class="grid grid-cols-2 gap-4 mb-7">
            <div class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-5 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1.5 active:scale-[0.97] transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/40 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <DocumentCheckIcon class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-[11px] font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/40 px-3 py-1.5 rounded-full tracking-wide uppercase">Actifs</span>
                </div>
                <p class="text-[2rem] font-bold text-gray-900 dark:text-white tracking-tight leading-none">{{ stats.active_contracts }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">Contrats</p>
            </div>

            <div class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-5 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1.5 active:scale-[0.97] transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-amber-400 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/40 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <ClockIcon class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/40 px-3 py-1.5 rounded-full tracking-wide uppercase">En attente</span>
                </div>
                <p class="text-[2rem] font-bold text-gray-900 dark:text-white tracking-tight leading-none">{{ stats.pending_invoices }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">Factures</p>
            </div>

            <div class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-5 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1.5 active:scale-[0.97] transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/40 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <CurrencyEuroIcon class="w-7 h-7 text-white" />
                    </div>
                </div>
                <p class="text-[1.5rem] font-bold text-gray-900 dark:text-white tracking-tight leading-none">{{ formatCurrency(stats.total_paid) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">Total paye</p>
            </div>

            <div class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-5 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1.5 active:scale-[0.97] transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/40 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <CubeIcon class="w-7 h-7 text-white" />
                    </div>
                </div>
                <p class="text-[2rem] font-bold text-gray-900 dark:text-white tracking-tight leading-none">{{ activeContracts?.length || 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">Mes box</p>
            </div>
        </div>

        <!-- Quick Actions with Modern Design -->
        <div class="mb-7">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 tracking-tight">Actions rapides</h3>
            <div class="grid grid-cols-4 gap-3">
                <Link
                    v-for="(action, index) in quickActions"
                    :key="action.route"
                    :href="route(action.route)"
                    class="flex flex-col items-center bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-4 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1.5 active:scale-90 transition-all duration-300 group"
                    :style="{ animationDelay: `${index * 50}ms` }"
                >
                    <div
                        class="w-[3.5rem] h-[3.5rem] rounded-2xl flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300"
                        :class="action.bgGradient"
                    >
                        <component :is="action.icon" class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-[11px] font-bold text-gray-700 dark:text-gray-300 text-center tracking-wide">{{ action.label }}</span>
                </Link>
            </div>
        </div>

        <!-- Storage Tips Carousel -->
        <div class="mb-7">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 tracking-tight">Conseils stockage</h3>
            <div class="relative overflow-hidden">
                <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide -mx-4 px-4">
                    <div
                        v-for="(tip, index) in storageTips"
                        :key="tip.id"
                        class="flex-shrink-0 w-[280px] bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-5 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 snap-center hover:shadow-2xl hover:-translate-y-1 active:scale-[0.98] transition-all duration-300 group"
                        :style="{ animationDelay: `${index * 100}ms` }"
                    >
                        <div class="flex items-start">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 flex-shrink-0 shadow-lg group-hover:scale-110 group-hover:rotate-6 transition-all duration-300"
                                :class="tip.bgColor"
                            >
                                <component :is="tip.icon" class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-[15px] tracking-tight">{{ tip.title }}</h4>
                                <p class="text-[13px] text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">{{ tip.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert for overdue invoices with animation -->
        <Transition
            enter-active-class="transition-all duration-600 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
            enter-from-class="opacity-0 scale-90"
            enter-to-class="opacity-100 scale-100"
        >
            <div v-if="overdueInvoices?.length > 0" class="mb-7">
                <div class="relative overflow-hidden bg-gradient-to-r from-red-500 via-red-600 to-rose-600 rounded-[1.75rem] p-6 text-white shadow-2xl shadow-red-500/40 group">
                    <!-- Animated pulse background -->
                    <div class="absolute inset-0 bg-gradient-to-r from-red-600/50 to-transparent animate-pulse-slow"></div>
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-xl"></div>

                    <div class="relative flex items-start">
                        <div class="w-14 h-14 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mr-4 flex-shrink-0 ring-2 ring-white/20 animate-bounce-subtle">
                            <ExclamationTriangleIcon class="w-8 h-8" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg tracking-tight">Attention !</h4>
                            <p class="text-red-100 mt-1 text-[15px]">
                                {{ overdueInvoices.length }} facture(s) en retard
                            </p>
                            <p class="text-[1.75rem] font-bold mt-2 tracking-tight">{{ formatCurrency(overdueTotal) }}</p>
                            <Link
                                :href="route('mobile.pay')"
                                class="inline-flex items-center mt-4 px-5 py-3 bg-white text-red-600 rounded-2xl font-bold text-sm hover:bg-red-50 active:scale-95 transition-all duration-200 shadow-lg shadow-red-900/20 group/btn"
                            >
                                Payer maintenant
                                <ArrowRightIcon class="w-4 h-4 ml-2 group-hover/btn:translate-x-1 transition-transform duration-200" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- My Boxes with Modern Cards -->
        <div class="mb-7" v-if="activeContracts?.length > 0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Mes Box</h3>
                <Link :href="route('mobile.boxes')" class="text-sm text-primary-600 dark:text-primary-400 font-bold flex items-center hover:text-primary-700 group">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform duration-200" />
                </Link>
            </div>

            <div class="space-y-4">
                <Link
                    v-for="(contract, index) in activeContracts.slice(0, 2)"
                    :key="contract.id"
                    :href="route('mobile.contracts.show', contract.id)"
                    class="block bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.75rem] p-5 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 hover:shadow-2xl hover:-translate-y-1 active:scale-[0.98] transition-all duration-300 group"
                    :style="{ animationDelay: `${index * 100}ms` }"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center mr-4 shadow-lg shadow-primary-500/40 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                                <CubeIcon class="w-8 h-8 text-white" />
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg tracking-tight">{{ contract.box?.name }}</h4>
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-1.5">
                                    <MapPinIcon class="w-4 h-4 mr-1.5" />
                                    {{ contract.box?.site?.name }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400 tracking-tight">{{ contract.monthly_price }}€</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">/mois</span>
                        </div>
                    </div>

                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700/50 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 font-medium">
                                <Square3Stack3DIcon class="w-4 h-4 mr-1.5" />
                                {{ contract.box?.area || 'N/A' }} m²
                            </div>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                Actif
                            </span>
                        </div>
                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 group-hover:translate-x-1 transition-all duration-300">
                            <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400" />
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Recent Invoices with Timeline Design -->
        <div class="mb-7" v-if="recentInvoices?.length > 0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Factures recentes</h3>
                <Link :href="route('mobile.invoices')" class="text-sm text-primary-600 dark:text-primary-400 font-bold flex items-center hover:text-primary-700 group">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform duration-200" />
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.75rem] shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 overflow-hidden divide-y divide-gray-100 dark:divide-gray-700/50">
                <Link
                    v-for="(invoice, index) in recentInvoices.slice(0, 3)"
                    :key="invoice.id"
                    :href="route('mobile.invoices.show', invoice.id)"
                    class="flex items-center justify-between p-5 hover:bg-gray-50/80 dark:hover:bg-gray-700/30 active:bg-gray-100 dark:active:bg-gray-700/50 transition-all duration-200 group"
                >
                    <div class="flex items-center">
                        <div
                            class="w-14 h-14 rounded-2xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-105 transition-transform duration-300"
                            :class="getStatusBgClass(invoice.status)"
                        >
                            <component
                                :is="getStatusIcon(invoice.status)"
                                class="w-7 h-7"
                                :class="getStatusIconClass(invoice.status)"
                            />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white tracking-tight">{{ invoice.invoice_number }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ formatDate(invoice.invoice_date) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="text-right mr-4">
                            <p class="font-bold text-gray-900 dark:text-white tracking-tight">{{ formatCurrency(invoice.total) }}</p>
                            <span
                                class="inline-block text-[11px] px-3 py-1 rounded-full font-bold mt-1 tracking-wide"
                                :class="getStatusBadgeClass(invoice.status)"
                            >
                                {{ getStatusLabel(invoice.status) }}
                            </span>
                        </div>
                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 group-hover:translate-x-1 transition-all duration-300">
                            <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400" />
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Recent Payments -->
        <div v-if="recentPayments?.length > 0" class="mb-7">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Derniers paiements</h3>
                <Link :href="route('mobile.payments')" class="text-sm text-primary-600 dark:text-primary-400 font-bold flex items-center hover:text-primary-700 group">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform duration-200" />
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.75rem] shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 overflow-hidden divide-y divide-gray-100 dark:divide-gray-700/50">
                <Link
                    v-for="(payment, index) in recentPayments.slice(0, 3)"
                    :key="payment.id"
                    :href="route('mobile.payments.show', payment.id)"
                    class="flex items-center justify-between p-5 hover:bg-gray-50/80 dark:hover:bg-gray-700/30 active:bg-gray-100 dark:active:bg-gray-700/50 transition-all duration-200 group"
                >
                    <div class="flex items-center">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-emerald-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg shadow-green-500/30 group-hover:scale-105 transition-transform duration-300">
                            <CheckCircleIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white tracking-tight">{{ payment.payment_number }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 font-medium">{{ formatDate(payment.paid_at) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="text-right mr-4">
                            <p class="font-bold text-green-600 dark:text-green-400 tracking-tight">+{{ formatCurrency(payment.amount) }}</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold">{{ getMethodLabel(payment.method) }}</span>
                        </div>
                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 group-hover:translate-x-1 transition-all duration-300">
                            <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400" />
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Quick Help Section -->
        <div class="mb-7">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Besoin d'aide ?</h3>
                <Link :href="route('mobile.help')" class="text-sm text-primary-600 dark:text-primary-400 font-bold flex items-center hover:text-primary-700 group">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform duration-200" />
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <Link
                    :href="route('mobile.support')"
                    class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-4 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 flex items-center hover:shadow-2xl hover:-translate-y-1 active:scale-[0.97] transition-all duration-300 group"
                >
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg shadow-blue-500/30 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <ChatBubbleLeftRightIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white text-[15px] tracking-tight">Chat</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">Support en ligne</p>
                    </div>
                </Link>
                <a
                    href="tel:+33100000000"
                    class="bg-white dark:bg-gray-800/95 backdrop-blur-lg rounded-[1.5rem] p-4 shadow-xl shadow-gray-200/60 dark:shadow-none border border-gray-100/80 dark:border-gray-700/50 flex items-center hover:shadow-2xl hover:-translate-y-1 active:scale-[0.97] transition-all duration-300 group"
                >
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center mr-4 shadow-lg shadow-green-500/30 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
                        <PhoneIcon class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white text-[15px] tracking-tight">Appeler</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">01 00 00 00 00</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!activeContracts?.length && !recentInvoices?.length" class="text-center py-16">
            <div class="w-28 h-28 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-xl">
                <CubeIcon class="w-14 h-14 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3 tracking-tight">Bienvenue sur Boxibox !</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-8 text-[15px] leading-relaxed max-w-sm mx-auto">Vous n'avez pas encore de box. Reservez votre premier espace de stockage.</p>
            <Link
                :href="route('mobile.reserve')"
                class="inline-flex items-center px-7 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-2xl font-bold text-base shadow-xl shadow-primary-500/40 hover:shadow-2xl hover:shadow-primary-500/50 active:scale-95 transition-all duration-300 group"
            >
                <PlusCircleIcon class="w-6 h-6 mr-2 group-hover:rotate-90 transition-transform duration-300" />
                Reserver un box
            </Link>
        </div>
    </MobileLayout>
</template>

<script setup>
import { computed, ref, reactive, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    DocumentCheckIcon,
    ClockIcon,
    CheckCircleIcon,
    CubeIcon,
    BanknotesIcon,
    PlusCircleIcon,
    KeyIcon,
    ChatBubbleLeftRightIcon,
    ChevronRightIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    MapPinIcon,
    ArrowRightIcon,
    CurrencyEuroIcon,
    ExclamationCircleIcon,
    Square3Stack3DIcon,
    BellIcon,
    GiftIcon,
    PhoneIcon,
    LightBulbIcon,
    ShieldCheckIcon,
    ArchiveBoxIcon,
    SunIcon,
    DevicePhoneMobileIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customer: Object,
    activeContracts: Array,
    recentInvoices: Array,
    recentPayments: Array,
    overdueInvoices: Array,
    stats: Object,
    notifications: {
        type: Array,
        default: () => []
    },
    promo: {
        type: Object,
        default: null
    }
})

// PWA Install functionality
const showInstallBanner = ref(false)
const showIOSInstructions = ref(false)
let deferredPrompt = null

const isIOS = () => {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
}

const isInStandaloneMode = () => {
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone === true
}

const checkInstallBanner = () => {
    // Don't show if already installed or dismissed
    if (isInStandaloneMode()) return
    if (localStorage.getItem('pwa-install-dismissed')) return

    // Show for iOS (needs manual instructions)
    if (isIOS()) {
        showInstallBanner.value = true
        return
    }

    // For Android/Chrome, wait for beforeinstallprompt event
}

const installApp = async () => {
    if (isIOS()) {
        // Show iOS instructions
        showIOSInstructions.value = true
        return
    }

    // For Android/Chrome
    if (deferredPrompt) {
        deferredPrompt.prompt()
        const { outcome } = await deferredPrompt.userChoice
        console.log('Install outcome:', outcome)
        deferredPrompt = null
        showInstallBanner.value = false
    } else if (window.installPWA) {
        // Use global install function from app.blade.php
        window.installPWA()
    }
}

const dismissInstallBanner = () => {
    showInstallBanner.value = false
    localStorage.setItem('pwa-install-dismissed', 'true')
}

const handleBeforeInstallPrompt = (e) => {
    e.preventDefault()
    deferredPrompt = e
    // Don't show if dismissed before
    if (!localStorage.getItem('pwa-install-dismissed')) {
        showInstallBanner.value = true
    }
}

const handlePwaInstallable = () => {
    if (!localStorage.getItem('pwa-install-dismissed')) {
        showInstallBanner.value = true
    }
}

// Dynamic greeting based on time of day
const greeting = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Bonjour'
    if (hour < 18) return 'Bon apres-midi'
    return 'Bonsoir'
})

// Weather data (mock - would be fetched from API in production)
const weather = reactive({
    temp: 12,
    icon: '☀️',
    description: 'Ensoleille'
})

// Update weather based on time (mock)
onMounted(() => {
    const hour = new Date().getHours()
    if (hour >= 6 && hour < 12) {
        weather.temp = 14
        weather.icon = '🌤️'
        weather.description = 'Nuageux'
    } else if (hour >= 12 && hour < 18) {
        weather.temp = 18
        weather.icon = '☀️'
        weather.description = 'Ensoleille'
    } else if (hour >= 18 && hour < 21) {
        weather.temp = 15
        weather.icon = '🌅'
        weather.description = 'Coucher de soleil'
    } else {
        weather.temp = 10
        weather.icon = '🌙'
        weather.description = 'Nuit claire'
    }

    // PWA Install listeners
    checkInstallBanner()
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.addEventListener('pwa-installable', handlePwaInstallable)
})

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.removeEventListener('pwa-installable', handlePwaInstallable)
})

// Unread notifications count
const unreadNotifications = computed(() => {
    return props.notifications?.filter(n => !n.read_at).length || 3
})

// Promo banner
const promoBanner = computed(() => {
    if (props.promo) return props.promo
    // Default promo if none provided
    return {
        title: '-20% sur le 2eme box',
        description: 'Offre valable jusqu\'au 31 janvier'
    }
})

// Storage tips carousel
const storageTips = [
    {
        id: 1,
        icon: LightBulbIcon,
        bgColor: 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-orange-500/30',
        title: 'Optimisez l\'espace',
        description: 'Utilisez des etageres pour maximiser le volume disponible'
    },
    {
        id: 2,
        icon: ShieldCheckIcon,
        bgColor: 'bg-gradient-to-br from-blue-400 to-indigo-600 shadow-blue-500/30',
        title: 'Protegez vos biens',
        description: 'Utilisez des housses anti-poussiere pour vos meubles'
    },
    {
        id: 3,
        icon: ArchiveBoxIcon,
        bgColor: 'bg-gradient-to-br from-green-400 to-emerald-600 shadow-green-500/30',
        title: 'Etiquetez tout',
        description: 'Numerotez vos cartons et tenez un inventaire'
    },
    {
        id: 4,
        icon: SunIcon,
        bgColor: 'bg-gradient-to-br from-purple-400 to-violet-600 shadow-purple-500/30',
        title: 'Aerez regulierement',
        description: 'Visitez votre box de temps en temps pour l\'aerer'
    }
]

// Quick actions configuration
const quickActions = [
    {
        route: 'mobile.pay',
        icon: BanknotesIcon,
        label: 'Payer',
        bgGradient: 'bg-gradient-to-br from-green-400 to-emerald-600 shadow-green-500/40'
    },
    {
        route: 'mobile.reserve',
        icon: PlusCircleIcon,
        label: 'Reserver',
        bgGradient: 'bg-gradient-to-br from-blue-400 to-indigo-600 shadow-blue-500/40'
    },
    {
        route: 'mobile.access',
        icon: KeyIcon,
        label: 'Acces',
        bgGradient: 'bg-gradient-to-br from-purple-400 to-violet-600 shadow-purple-500/40'
    },
    {
        route: 'mobile.support',
        icon: ChatBubbleLeftRightIcon,
        label: 'Support',
        bgGradient: 'bg-gradient-to-br from-orange-400 to-red-500 shadow-orange-500/40'
    },
]

const customerName = computed(() => {
    if (!props.customer) return 'Client'
    if (props.customer.type === 'company') {
        return props.customer.company_name
    }
    return `${props.customer.first_name} ${props.customer.last_name}`
})

const userInitial = computed(() => {
    if (!props.customer) return 'C'
    return props.customer.first_name?.charAt(0)?.toUpperCase() || 'C'
})

const nextPaymentDate = computed(() => {
    if (props.recentInvoices?.length > 0) {
        const pendingInvoice = props.recentInvoices.find(i => i.status === 'sent')
        if (pendingInvoice?.due_date) {
            return formatDate(pendingInvoice.due_date)
        }
    }
    return '-'
})

const overdueTotal = computed(() => {
    return props.overdueInvoices?.reduce((sum, inv) => sum + parseFloat(inv.total || 0), 0) || 0
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    })
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        sent: 'A payer',
        paid: 'Payee',
        overdue: 'En retard',
        cancelled: 'Annulee',
    }
    return labels[status] || status
}

const getStatusBgClass = (status) => {
    const classes = {
        paid: 'bg-gradient-to-br from-green-400 to-emerald-600 shadow-green-500/30',
        sent: 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-500/30',
        overdue: 'bg-gradient-to-br from-red-400 to-rose-600 shadow-red-500/30',
        draft: 'bg-gradient-to-br from-gray-400 to-gray-600 shadow-gray-500/30',
    }
    return classes[status] || 'bg-gradient-to-br from-gray-400 to-gray-600 shadow-gray-500/30'
}

const getStatusIcon = (status) => {
    const icons = {
        paid: CheckCircleIcon,
        sent: ClockIcon,
        overdue: ExclamationCircleIcon,
        draft: DocumentTextIcon,
    }
    return icons[status] || DocumentTextIcon
}

const getStatusIconClass = (status) => {
    return 'text-white'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        paid: 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-400',
        sent: 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400',
        overdue: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-400',
        draft: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400',
    }
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400'
}

const getMethodLabel = (method) => {
    const labels = {
        card: 'Carte',
        bank_transfer: 'Virement',
        sepa: 'SEPA',
        cash: 'Especes',
        check: 'Cheque',
    }
    return labels[method] || method
}
</script>

<style scoped>
/* Custom animations for native feel */
@keyframes float {
    0%, 100% {
        transform: translateY(0) translateX(0);
    }
    50% {
        transform: translateY(-10px) translateX(5px);
    }
}

@keyframes float-delayed {
    0%, 100% {
        transform: translateY(0) translateX(0);
    }
    50% {
        transform: translateY(-8px) translateX(-5px);
    }
}

@keyframes pulse-slow {
    0%, 100% {
        opacity: 0.6;
    }
    50% {
        opacity: 1;
    }
}

@keyframes bounce-subtle {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 8s ease-in-out infinite;
    animation-delay: 2s;
}

.animate-pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
}

.animate-bounce-subtle {
    animation: bounce-subtle 2s ease-in-out infinite;
}

/* Hide scrollbar for carousel */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Smooth touch interactions */
* {
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
}

/* Better touch response */
a, button {
    touch-action: manipulation;
}

/* Prevent text selection on interactive elements */
.group {
    user-select: none;
}
</style>
