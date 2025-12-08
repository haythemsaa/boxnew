<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Sidebar - NOA Style (Light) -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'
            ]"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transition-all duration-300 ease-in-out lg:translate-x-0 flex flex-col"
            data-tutorial="sidebar"
        >
            <!-- Logo Section - NOA Style -->
            <div class="flex h-16 items-center justify-between px-5 border-b border-gray-100">
                <div class="flex items-center space-x-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg text-white font-bold text-lg" style="background: linear-gradient(135deg, #8fbd56 0%, #38cab3 100%);">
                        <ArchiveBoxIcon class="h-5 w-5" />
                    </div>
                    <transition name="fade">
                        <span v-if="!sidebarCollapsed" class="text-lg font-bold text-gray-900 tracking-tight">
                            {{ $page.props.tenant?.name || 'boxibox' }}
                        </span>
                    </transition>
                </div>
                <button
                    @click="sidebarOpen = false"
                    class="lg:hidden text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- Navigation - NOA Style -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <!-- Main Section -->
                <div class="mb-5">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
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
                        data-tutorial="sites-menu"
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
                        data-tutorial="boxes-menu"
                    >
                        <template #icon>
                            <ArchiveBoxIcon class="h-5 w-5" />
                        </template>
                        Boxes
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.plan.interactive')"
                        :active="route().current('tenant.plan.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <MapIcon class="h-5 w-5" />
                        </template>
                        Plan interactif
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.bookings.index')"
                        :active="route().current('tenant.bookings.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <CalendarDaysIcon class="h-5 w-5" />
                        </template>
                        Réservations
                    </SidebarLink>
                </div>

                <!-- CRM Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        CRM
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
                        :href="route('tenant.crm.leads.index')"
                        :active="route().current('tenant.crm.leads.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <FunnelIcon class="h-5 w-5" />
                        </template>
                        Leads
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.crm.campaigns.index')"
                        :active="route().current('tenant.crm.campaigns.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <MegaphoneIcon class="h-5 w-5" />
                        </template>
                        Campagnes
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.customers.index')"
                        :active="route().current('tenant.customers.*')"
                        :collapsed="sidebarCollapsed"
                        data-tutorial="customers-menu"
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
                        data-tutorial="contracts-menu"
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
                        data-tutorial="invoices-menu"
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
                        :href="route('tenant.payments.index')"
                        :active="route().current('tenant.payments.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <BanknotesIcon class="h-5 w-5" />
                        </template>
                        Paiements
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

                    <SidebarLink
                        :href="route('tenant.pricing.dashboard')"
                        :active="route().current('tenant.pricing.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <CurrencyEuroIcon class="h-5 w-5" />
                        </template>
                        Tarification
                    </SidebarLink>
                </div>

                <!-- Analytics & AI Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Analytics & IA
                    </p>

                    <!-- AI Copilot - Featured Link -->
                    <SidebarLink
                        :href="route('tenant.copilot.index')"
                        :active="route().current('tenant.copilot.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-purple-600/20 to-indigo-600/20 border border-purple-500/30 hover:from-purple-600/30 hover:to-indigo-600/30"
                    >
                        <template #icon>
                            <SparklesIcon class="h-5 w-5 text-purple-400" />
                        </template>
                        <span class="flex items-center gap-2">
                            Copilot IA
                            <span class="text-[10px] bg-purple-500 text-white px-1.5 py-0.5 rounded-full font-bold">NEW</span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.analytics.revenue')"
                        :active="route().current('tenant.analytics.revenue')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ChartBarIcon class="h-5 w-5" />
                        </template>
                        Revenus
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.analytics.occupancy')"
                        :active="route().current('tenant.analytics.occupancy')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ChartPieIcon class="h-5 w-5" />
                        </template>
                        Occupation
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.analytics.operations')"
                        :active="route().current('tenant.analytics.operations')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ClipboardDocumentListIcon class="h-5 w-5" />
                        </template>
                        Opérations
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.analytics.marketing')"
                        :active="route().current('tenant.analytics.marketing')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <PresentationChartLineIcon class="h-5 w-5" />
                        </template>
                        Marketing
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.analytics.predictive.index')"
                        :active="route().current('tenant.analytics.predictive.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ArrowTrendingUpIcon class="h-5 w-5" />
                        </template>
                        Prédictif
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

                <!-- Operations Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Opérations
                    </p>
                    <SidebarLink
                        :href="route('tenant.maintenance.index')"
                        :active="route().current('tenant.maintenance.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <WrenchScrewdriverIcon class="h-5 w-5" />
                        </template>
                        Maintenance
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.inspections.index')"
                        :active="route().current('tenant.inspections.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ClipboardDocumentCheckIcon class="h-5 w-5" />
                        </template>
                        Inspections
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.overdue.index')"
                        :active="route().current('tenant.overdue.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ExclamationTriangleIcon class="h-5 w-5" />
                        </template>
                        Impayés
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.staff.index')"
                        :active="route().current('tenant.staff.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <UserGroupIcon class="h-5 w-5" />
                        </template>
                        Personnel
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.iot.dashboard')"
                        :active="route().current('tenant.iot.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <CpuChipIcon class="h-5 w-5" />
                        </template>
                        IoT & Smart Entry
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.access-control.dashboard')"
                        :active="route().current('tenant.access-control.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <LockClosedIcon class="h-5 w-5" />
                        </template>
                        Contrôle d'accès
                    </SidebarLink>
                </div>

                <!-- Reports Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Rapports
                    </p>
                    <SidebarLink
                        :href="route('tenant.reports.index')"
                        :active="route().current('tenant.reports.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <DocumentChartBarIcon class="h-5 w-5" />
                        </template>
                        Rapports
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.reports.rent-roll')"
                        :active="route().current('tenant.reports.rent-roll')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <TableCellsIcon class="h-5 w-5" />
                        </template>
                        Rent Roll
                    </SidebarLink>
                </div>

                <!-- Marketing Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Marketing
                    </p>
                    <SidebarLink
                        :href="route('tenant.calculator.index')"
                        :active="route().current('tenant.calculator.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <CalculatorIcon class="h-5 w-5" />
                        </template>
                        Calculateur
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.loyalty.index')"
                        :active="route().current('tenant.loyalty.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <GiftIcon class="h-5 w-5" />
                        </template>
                        Fidélité
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.reviews.index')"
                        :active="route().current('tenant.reviews.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <StarIcon class="h-5 w-5" />
                        </template>
                        Avis clients
                    </SidebarLink>
                </div>

                <!-- Communication Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Communication
                    </p>
                    <SidebarLink
                        :href="route('tenant.notifications.index')"
                        :active="route().current('tenant.notifications.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <BellIcon class="h-5 w-5" />
                        </template>
                        Notifications
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.messages.index')"
                        :active="route().current('tenant.messages.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <EnvelopeIcon class="h-5 w-5" />
                        </template>
                        Messages
                    </SidebarLink>
                </div>

                <!-- Security Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Sécurité
                    </p>
                    <SidebarLink
                        :href="route('tenant.access-control.dashboard')"
                        :active="route().current('tenant.access-control.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <ShieldCheckIcon class="h-5 w-5" />
                        </template>
                        Contrôle d'accès
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.gdpr.index')"
                        :active="route().current('tenant.gdpr.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <LockClosedIcon class="h-5 w-5" />
                        </template>
                        RGPD
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.sustainability.index')"
                        :active="route().current('tenant.sustainability.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                        {{ $t('sustainability.dashboard') }}
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.video-calls.index')"
                        :active="route().current('tenant.video-calls.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </template>
                        {{ $t('video_calls.live_agent') }}
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.valet.index')"
                        :active="route().current('tenant.valet.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </template>
                        {{ $t('valet.title') }}
                    </SidebarLink>
                </div>

                <!-- Settings Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Configuration
                    </p>
                    <SidebarLink
                        :href="route('tenant.settings.index')"
                        :active="route().current('tenant.settings.*')"
                        :collapsed="sidebarCollapsed"
                        data-tutorial="settings-menu"
                    >
                        <template #icon>
                            <Cog6ToothIcon class="h-5 w-5" />
                        </template>
                        Paramètres
                    </SidebarLink>
                </div>
            </nav>

            <!-- Collapse Button (Desktop) - NOA Style -->
            <button
                @click="toggleCollapse"
                class="hidden lg:flex items-center justify-center h-9 mx-3 mb-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 transition-colors"
            >
                <ChevronLeftIcon v-if="!sidebarCollapsed" class="h-5 w-5" />
                <ChevronRightIcon v-else class="h-5 w-5" />
            </button>

            <!-- User Section - NOA Style -->
            <div class="border-t border-gray-100 p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full text-white font-semibold text-sm" style="background: linear-gradient(135deg, #8fbd56 0%, #38cab3 100%);">
                        {{ $page.props.auth.user?.name?.charAt(0) || 'U' }}
                    </div>
                    <transition name="fade">
                        <div v-if="!sidebarCollapsed" class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $page.props.auth.user?.name }}
                            </p>
                            <p class="text-xs text-gray-500 truncate">
                                {{ $page.props.auth.user?.email }}
                            </p>
                        </div>
                    </transition>
                    <div class="relative" v-if="!sidebarCollapsed">
                        <button
                            @click="showUserMenu = !showUserMenu"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                        >
                            <EllipsisVerticalIcon class="h-5 w-5" />
                        </button>
                        <transition name="dropdown">
                            <div v-if="showUserMenu" class="absolute bottom-full right-0 mb-2 w-48 rounded-xl bg-white shadow-xl border border-gray-100 py-1 z-50">
                                <Link
                                    :href="route('tenant.settings.index')"
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
        <div :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'" class="transition-all duration-300">
            <!-- Top Header - NOA Style -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <!-- Mobile Menu Button -->
                    <button
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                    >
                        <Bars3Icon class="h-6 w-6" />
                    </button>

                    <!-- Search Bar - NOA Style -->
                    <div class="hidden md:flex items-center flex-1 max-w-md">
                        <div class="flex items-center w-full bg-gray-100 rounded-lg px-4 py-2.5">
                            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 mr-3" />
                            <input
                                type="text"
                                placeholder="Rechercher..."
                                class="bg-transparent border-none outline-none text-sm text-gray-600 placeholder-gray-400 flex-1"
                            />
                        </div>
                    </div>

                    <!-- Page Title & Breadcrumb (Mobile & alongside search on desktop) -->
                    <div class="flex-1 flex items-center md:hidden">
                        <div>
                            <h1 v-if="title" class="text-lg font-bold text-gray-900">
                                {{ title }}
                            </h1>
                        </div>
                    </div>

                    <!-- Right side breadcrumb - NOA Style -->
                    <div class="hidden lg:flex items-center gap-2 text-sm">
                        <Link :href="route('tenant.dashboard')" class="text-primary-400 hover:text-primary-500 transition-colors">
                            Accueil
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
                        <div class="relative" data-tutorial="quick-actions">
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

                        <!-- Tutorial Help Button -->
                        <div class="relative" data-tutorial="help-button">
                            <button
                                @click="showTutorialMenu = !showTutorialMenu"
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                title="Aide & Guide"
                            >
                                <QuestionMarkCircleIcon class="h-5 w-5" />
                            </button>
                            <transition name="dropdown">
                                <div v-if="showTutorialMenu" class="absolute right-0 mt-2 w-64 rounded-xl bg-white shadow-xl border border-gray-100 py-2 z-50">
                                    <div class="px-4 py-2 border-b border-gray-100">
                                        <h3 class="text-sm font-semibold text-gray-900">Aide & Tutoriel</h3>
                                    </div>
                                    <button
                                        @click="startTutorial"
                                        class="flex items-center w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Lancer le guide interactif
                                    </button>
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <label class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-gray-50 transition">
                                        <span class="text-sm text-gray-700">Afficher les guides</span>
                                        <button
                                            @click="toggleTutorialEnabled"
                                            :class="isEnabled ? 'bg-primary-600' : 'bg-gray-300'"
                                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        >
                                            <span
                                                :class="isEnabled ? 'translate-x-4' : 'translate-x-0'"
                                                class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                            ></span>
                                        </button>
                                    </label>
                                    <button
                                        @click="resetTutorials(); showTutorialMenu = false"
                                        class="flex items-center w-full px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition"
                                    >
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Reinitialiser les guides
                                    </button>
                                </div>
                            </transition>
                        </div>

                        <!-- Notifications -->
                        <div class="relative" data-tutorial="notifications">
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

        <!-- Tutorial Guide Component -->
        <TutorialGuide
            ref="tutorialGuideRef"
            tour-id="main"
            :auto-start="shouldAutoStart()"
            :show-floating-button="false"
        />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import SidebarLink from '@/Components/SidebarLink.vue'
import TutorialGuide from '@/Components/Tutorial/TutorialGuide.vue'
import { useTutorial } from '@/Composables/useTutorial'
import { QuestionMarkCircleIcon } from '@heroicons/vue/24/outline'
import {
    HomeIcon,
    BuildingOffice2Icon,
    ArchiveBoxIcon,
    UsersIcon,
    UserPlusIcon,
    UserGroupIcon,
    DocumentTextIcon,
    ReceiptPercentIcon,
    DocumentDuplicateIcon,
    DocumentChartBarIcon,
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
    CalendarDaysIcon,
    FunnelIcon,
    MegaphoneIcon,
    BanknotesIcon,
    CurrencyEuroIcon,
    ChartBarIcon,
    ChartPieIcon,
    ClipboardDocumentListIcon,
    ClipboardDocumentCheckIcon,
    PresentationChartLineIcon,
    SparklesIcon,
    ArrowTrendingUpIcon,
    ShieldCheckIcon,
    WrenchScrewdriverIcon,
    TableCellsIcon,
    CalculatorIcon,
    GiftIcon,
    StarIcon,
    LockClosedIcon,
    CpuChipIcon,
    EnvelopeIcon,
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
const tutorialGuideRef = ref(null)
const showTutorialMenu = ref(false)

// Tutorial composable
const { isEnabled, setEnabled, shouldAutoStart, resetTutorials } = useTutorial()

const startTutorial = () => {
    showTutorialMenu.value = false
    if (tutorialGuideRef.value) {
        tutorialGuideRef.value.startTour()
    }
}

const toggleTutorialEnabled = () => {
    setEnabled(!isEnabled.value)
}

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
        showTutorialMenu.value = false
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
