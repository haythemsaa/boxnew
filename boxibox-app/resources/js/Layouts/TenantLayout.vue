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
            role="navigation"
            aria-label="Menu principal"
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
                    aria-label="Fermer le menu"
                >
                    <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                </button>
            </div>

            <!-- Navigation - NOA Style -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <!-- Main Section -->
                <div class="mb-5">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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

                    <!-- Plan Interactif Pro -->
                    <SidebarLink
                        :href="route('tenant.plan.interactive-pro')"
                        :active="route().current('tenant.plan.interactive-pro')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-cyan-600/20 to-blue-600/20 border border-cyan-500/30 hover:from-cyan-600/30 hover:to-blue-600/30"
                    >
                        <template #icon>
                            <MapIcon class="h-5 w-5 text-cyan-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            Plan Interactif
                            <span class="text-xs bg-gradient-to-r from-cyan-500 to-blue-500 text-white px-1.5 py-0.5 rounded-full font-bold">PRO</span>
                        </span>
                    </SidebarLink>

                    <!-- Editeur de Plan Pro -->
                    <SidebarLink
                        :href="route('tenant.plan.editor-pro')"
                        :active="route().current('tenant.plan.editor-pro')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <PencilSquareIcon class="h-5 w-5" />
                        </template>
                        Editeur de Plan
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
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        CRM
                    </p>

                    <!-- AI Lead Scoring - Featured Link -->
                    <SidebarLink
                        :href="route('tenant.crm.ai-scoring.dashboard')"
                        :active="route().current('tenant.crm.ai-scoring.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-rose-600/20 to-orange-600/20 border border-rose-500/30 hover:from-rose-600/30 hover:to-orange-600/30"
                    >
                        <template #icon>
                            <FireIcon class="h-5 w-5 text-rose-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            AI Lead Scoring
                            <span class="text-xs bg-gradient-to-r from-rose-500 to-orange-500 text-white px-1.5 py-0.5 rounded-full font-bold">IA</span>
                        </span>
                        <template #badge>
                            <span v-if="$page.props.hotLeadsCount" class="ml-auto bg-rose-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">
                                {{ $page.props.hotLeadsCount }}
                            </span>
                        </template>
                    </SidebarLink>

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

                <!-- Boutique Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Boutique
                    </p>
                    <SidebarLink
                        :href="route('tenant.products.index')"
                        :active="route().current('tenant.products.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <CubeIcon class="h-5 w-5" />
                        </template>
                        Produits
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.sales.index')"
                        :active="route().current('tenant.sales.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-emerald-600/20 to-teal-600/20 border border-emerald-500/30 hover:from-emerald-600/30 hover:to-teal-600/30"
                    >
                        <template #icon>
                            <ShoppingCartIcon class="h-5 w-5 text-emerald-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            Ventes
                            <span class="text-xs bg-emerald-500 text-white px-1.5 py-0.5 rounded-full font-bold">POS</span>
                        </span>
                    </SidebarLink>
                </div>

                <!-- Finance Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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
                            <span class="text-xs bg-purple-500 text-white px-1.5 py-0.5 rounded-full font-bold">NEW</span>
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

                    <SidebarLink
                        :href="route('tenant.analytics.kpis')"
                        :active="route().current('tenant.analytics.kpis*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-indigo-600/20 to-purple-600/20 border border-indigo-500/30 hover:from-indigo-600/30 hover:to-purple-600/30"
                    >
                        <template #icon>
                            <ChartBarSquareIcon class="h-5 w-5 text-indigo-400" />
                        </template>
                        <span class="flex items-center gap-2">
                            KPIs Self-Storage
                            <span class="text-xs bg-indigo-500 text-white px-1.5 py-0.5 rounded-full font-bold">PRO</span>
                        </span>
                    </SidebarLink>
                </div>

                <!-- Communication Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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
                        :href="route('tenant.live-chat.index')"
                        :active="route().current('tenant.live-chat.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-green-600/20 to-emerald-600/20 border border-green-500/30 hover:from-green-600/30 hover:to-emerald-600/30"
                    >
                        <template #icon>
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                            </svg>
                        </template>
                        <span class="flex items-center gap-2">
                            Chat en direct
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.chatbot.index')"
                        :active="route().current('tenant.chatbot.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-blue-600/20 to-cyan-600/20 border border-blue-500/30 hover:from-blue-600/30 hover:to-cyan-600/30"
                    >
                        <template #icon>
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </template>
                        <span class="flex items-center gap-2">
                            Chatbot IA
                            <span class="text-xs bg-blue-500 text-white px-1.5 py-0.5 rounded-full font-bold">24/7</span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.support.index')"
                        :active="route().current('tenant.support.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <LifebuoyIcon class="h-5 w-5" />
                        </template>
                        Support Client
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
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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
                        :href="route('tenant.smart-locks.index')"
                        :active="route().current('tenant.smart-locks.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-indigo-600/20 to-violet-600/20 border border-indigo-500/30 hover:from-indigo-600/30 hover:to-violet-600/30"
                    >
                        <template #icon>
                            <LockClosedIcon class="h-5 w-5 text-indigo-400" />
                        </template>
                        <span class="flex items-center gap-2">
                            Smart Locks
                            <span class="text-xs bg-indigo-500 text-white px-1.5 py-0.5 rounded-full font-bold">PRO</span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.iot.dashboard')"
                        :active="route().current('tenant.iot.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <CpuChipIcon class="h-5 w-5" />
                        </template>
                        IoT & Capteurs
                    </SidebarLink>

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
                        :href="route('tenant.media.index')"
                        :active="route().current('tenant.media.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-purple-600/20 to-pink-600/20 border border-purple-500/30 hover:from-purple-600/30 hover:to-pink-600/30"
                    >
                        <template #icon>
                            <PhotoIcon class="h-5 w-5 text-purple-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            Galerie Media
                            <span class="text-xs bg-purple-500 text-white px-1.5 py-0.5 rounded-full font-bold">360°</span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.self-service.index')"
                        :active="route().current('tenant.self-service.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-emerald-600/20 to-teal-600/20 border border-emerald-500/30 hover:from-emerald-600/30 hover:to-teal-600/30"
                    >
                        <template #icon>
                            <KeyIcon class="h-5 w-5 text-emerald-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            Self-Service 24/7
                            <span class="text-xs bg-emerald-500 text-white px-1.5 py-0.5 rounded-full font-bold">NEW</span>
                        </span>
                    </SidebarLink>
                </div>

                <!-- Reports Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
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

                    <SidebarLink
                        :href="route('tenant.google-reserve.index')"
                        :active="route().current('tenant.google-reserve.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-red-600/20 to-yellow-600/20 border border-red-500/30 hover:from-red-600/30 hover:to-yellow-600/30"
                    >
                        <template #icon>
                            <svg class="h-5 w-5 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
                            </svg>
                        </template>
                        <span class="flex items-center gap-2">
                            Google Reserve
                            <span class="text-xs bg-gradient-to-r from-red-500 to-yellow-500 text-white px-1.5 py-0.5 rounded-full font-bold">NEW</span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.marketplaces.index')"
                        :active="route().current('tenant.marketplaces.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-green-600/20 to-teal-600/20 border border-green-500/30 hover:from-green-600/30 hover:to-teal-600/30"
                    >
                        <template #icon>
                            <GlobeAltIcon class="h-5 w-5 text-green-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            Marketplaces
                            <span class="text-xs bg-gradient-to-r from-green-500 to-teal-500 text-white px-1.5 py-0.5 rounded-full font-bold">+30%</span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.call-tracking.index')"
                        :active="route().current('tenant.call-tracking.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <PhoneIcon class="h-5 w-5" />
                        </template>
                        Call Tracking
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.kiosks.index')"
                        :active="route().current('tenant.kiosks.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-sky-600/20 to-indigo-600/20 border border-sky-500/30 hover:from-sky-600/30 hover:to-indigo-600/30"
                    >
                        <template #icon>
                            <ComputerDesktopIcon class="h-5 w-5 text-sky-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            Kiosques
                            <span class="text-xs bg-gradient-to-r from-sky-500 to-indigo-500 text-white px-1.5 py-0.5 rounded-full font-bold">24/7</span>
                        </span>
                    </SidebarLink>

                    <SidebarLink
                        :href="route('tenant.notifications.index')"
                        :active="route().current('tenant.notifications.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <BellIcon class="h-5 w-5" />
                        </template>
                        Notifications Push
                    </SidebarLink>
                </div>

                <!-- Assurances & Services Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Assurances & Services
                    </p>
                    <SidebarLink
                        :href="route('tenant.insurance.index')"
                        :active="route().current('tenant.insurance.*')"
                        :collapsed="sidebarCollapsed"
                        class="bg-gradient-to-r from-amber-600/20 to-orange-600/20 border border-amber-500/30 hover:from-amber-600/30 hover:to-orange-600/30"
                    >
                        <template #icon>
                            <ShieldCheckIcon class="h-5 w-5 text-amber-500" />
                        </template>
                        <span class="flex items-center gap-2">
                            Assurances
                            <span class="text-xs bg-amber-500 text-white px-1.5 py-0.5 rounded-full font-bold">NEW</span>
                        </span>
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
                        Visio Agent
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
                        Service Valet
                    </SidebarLink>
                </div>

                <!-- Conformité Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Conformité
                    </p>
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
                        RSE & Durabilité
                    </SidebarLink>
                </div>

                <!-- Settings Section -->
                <div class="mb-6">
                    <p v-if="!sidebarCollapsed" class="px-3 mb-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Configuration
                    </p>
                    <SidebarLink
                        :href="route('tenant.settings.index')"
                        :active="route().current('tenant.settings.index') || route().current('tenant.settings.email-templates.*')"
                        :collapsed="sidebarCollapsed"
                        data-tutorial="settings-menu"
                    >
                        <template #icon>
                            <Cog6ToothIcon class="h-5 w-5" />
                        </template>
                        Paramètres
                    </SidebarLink>
                    <SidebarLink
                        :href="route('tenant.users.index')"
                        :active="route().current('tenant.users.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <UserCircleIcon class="h-5 w-5" />
                        </template>
                        Utilisateurs
                    </SidebarLink>
                    <SidebarLink
                        :href="route('tenant.settings.communication-providers.index')"
                        :active="route().current('tenant.settings.communication-providers.*')"
                        :collapsed="sidebarCollapsed"
                    >
                        <template #icon>
                            <SignalIcon class="h-5 w-5" />
                        </template>
                        Email & SMS
                        <template #badge>
                            <span class="ml-auto text-xs bg-gradient-to-r from-indigo-500 to-purple-500 text-white px-1.5 py-0.5 rounded-full font-bold">
                                API
                            </span>
                        </template>
                    </SidebarLink>
                </div>
            </nav>

            <!-- Collapse Button (Desktop) - NOA Style -->
            <button
                @click="toggleCollapse"
                class="hidden lg:flex items-center justify-center h-9 mx-3 mb-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 transition-colors"
                :aria-label="sidebarCollapsed ? 'Agrandir le menu' : 'Réduire le menu'"
                :aria-expanded="!sidebarCollapsed"
            >
                <ChevronLeftIcon v-if="!sidebarCollapsed" class="h-5 w-5" aria-hidden="true" />
                <ChevronRightIcon v-else class="h-5 w-5" aria-hidden="true" />
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
                        aria-label="Ouvrir le menu"
                        aria-controls="main-navigation"
                        :aria-expanded="sidebarOpen"
                    >
                        <Bars3Icon class="h-6 w-6" aria-hidden="true" />
                    </button>

                    <!-- Search Bar - NOA Style -->
                    <div class="hidden md:flex items-center flex-1 max-w-md" role="search">
                        <div class="flex items-center w-full bg-gray-100 rounded-lg px-4 py-2.5">
                            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 mr-3" aria-hidden="true" />
                            <input
                                type="search"
                                placeholder="Rechercher..."
                                class="bg-transparent border-none outline-none text-sm text-gray-600 placeholder-gray-400 flex-1"
                                aria-label="Rechercher dans l'application"
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
                                aria-label="Actions rapides"
                                :aria-expanded="showQuickActions"
                                aria-haspopup="true"
                            >
                                <PlusIcon class="h-5 w-5" aria-hidden="true" />
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
                                    <Link :href="route('tenant.sales.create')" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        <ShoppingCartIcon class="h-4 w-4 mr-3 text-teal-500" />
                                        Nouvelle vente
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
                                    <Link
                                        :href="route('tenant.onboarding.index')"
                                        @click="showTutorialMenu = false"
                                        class="flex items-center w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                        Guide de demarrage
                                    </Link>
                                    <button
                                        @click="startTutorial"
                                        class="flex items-center w-full px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Lancer le tutoriel interactif
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
                        <div data-tutorial="notifications">
                            <NotificationCenter />
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 pb-20 sm:p-6 sm:pb-6 lg:p-8" role="main" aria-label="Contenu principal">
                <!-- Flash Messages -->
                <transition name="slide-down">
                    <div v-if="$page.props.flash?.success" class="mb-6 flex items-center p-4 rounded-xl bg-emerald-50 border border-emerald-200 animate-fade-in-down" role="alert" aria-live="polite">
                        <CheckCircleIcon class="h-5 w-5 text-emerald-500 mr-3 flex-shrink-0" aria-hidden="true" />
                        <p class="text-sm text-emerald-800">{{ $page.props.flash.success }}</p>
                        <button @click="dismissFlash('success')" class="ml-auto p-1 hover:bg-emerald-100 rounded-lg transition-colors" aria-label="Fermer le message">
                            <XMarkIcon class="h-4 w-4 text-emerald-500" aria-hidden="true" />
                        </button>
                    </div>
                </transition>
                <transition name="slide-down">
                    <div v-if="$page.props.flash?.error" class="mb-6 flex items-center p-4 rounded-xl bg-red-50 border border-red-200 animate-fade-in-down" role="alert" aria-live="assertive">
                        <ExclamationCircleIcon class="h-5 w-5 text-red-500 mr-3 flex-shrink-0" aria-hidden="true" />
                        <p class="text-sm text-red-800">{{ $page.props.flash.error }}</p>
                        <button @click="dismissFlash('error')" class="ml-auto p-1 hover:bg-red-100 rounded-lg transition-colors" aria-label="Fermer l'erreur">
                            <XMarkIcon class="h-4 w-4 text-red-500" aria-hidden="true" />
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

            <!-- Footer (hidden on mobile to make room for bottom nav) -->
            <footer class="hidden sm:block border-t border-gray-200 bg-white/50 py-4 px-8">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <p>&copy; {{ new Date().getFullYear() }} Boxibox. Tous droits réservés.</p>
                    <p>Version 1.0.0</p>
                </div>
            </footer>

            <!-- Mobile Bottom Navigation -->
            <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 sm:hidden pb-safe" role="navigation" aria-label="Navigation mobile">
                <div class="flex items-center justify-around h-16">
                    <Link
                        :href="route('tenant.dashboard')"
                        :class="[
                            'flex flex-col items-center justify-center flex-1 h-full transition-colors',
                            route().current('tenant.dashboard') ? 'text-primary-600' : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <HomeIcon class="h-6 w-6" />
                        <span class="text-xs mt-1 font-medium">Accueil</span>
                    </Link>
                    <Link
                        :href="route('tenant.customers.index')"
                        :class="[
                            'flex flex-col items-center justify-center flex-1 h-full transition-colors',
                            route().current('tenant.customers.*') ? 'text-primary-600' : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <UsersIcon class="h-6 w-6" />
                        <span class="text-xs mt-1 font-medium">Clients</span>
                    </Link>
                    <button
                        @click="showMobileQuickActions = true"
                        class="flex flex-col items-center justify-center flex-1 h-full text-white -mt-4"
                    >
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-gradient-to-r from-primary-500 to-primary-600 shadow-lg">
                            <PlusIcon class="h-6 w-6" />
                        </div>
                    </button>
                    <Link
                        :href="route('tenant.contracts.index')"
                        :class="[
                            'flex flex-col items-center justify-center flex-1 h-full transition-colors',
                            route().current('tenant.contracts.*') ? 'text-primary-600' : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <DocumentTextIcon class="h-6 w-6" />
                        <span class="text-xs mt-1 font-medium">Contrats</span>
                    </Link>
                    <button
                        @click="sidebarOpen = true"
                        class="flex flex-col items-center justify-center flex-1 h-full text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <Bars3Icon class="h-6 w-6" />
                        <span class="text-xs mt-1 font-medium">Menu</span>
                    </button>
                </div>
            </nav>

            <!-- Mobile Quick Actions Bottom Sheet -->
            <transition name="slide-up">
                <div v-if="showMobileQuickActions" class="fixed inset-0 z-50 sm:hidden">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showMobileQuickActions = false"></div>
                    <div class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl pb-safe">
                        <div class="flex justify-center py-3">
                            <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                        </div>
                        <div class="px-4 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <Link :href="route('tenant.customers.create')" class="flex flex-col items-center p-4 rounded-xl bg-blue-50 hover:bg-blue-100 transition-colors">
                                    <UserPlusIcon class="h-8 w-8 text-blue-600 mb-2" />
                                    <span class="text-sm font-medium text-blue-900">Client</span>
                                </Link>
                                <Link :href="route('tenant.contracts.create')" class="flex flex-col items-center p-4 rounded-xl bg-emerald-50 hover:bg-emerald-100 transition-colors">
                                    <DocumentPlusIcon class="h-8 w-8 text-emerald-600 mb-2" />
                                    <span class="text-sm font-medium text-emerald-900">Contrat</span>
                                </Link>
                                <Link :href="route('tenant.invoices.create')" class="flex flex-col items-center p-4 rounded-xl bg-purple-50 hover:bg-purple-100 transition-colors">
                                    <DocumentTextIcon class="h-8 w-8 text-purple-600 mb-2" />
                                    <span class="text-sm font-medium text-purple-900">Facture</span>
                                </Link>
                                <Link :href="route('tenant.payments.create')" class="flex flex-col items-center p-4 rounded-xl bg-amber-50 hover:bg-amber-100 transition-colors">
                                    <BanknotesIcon class="h-8 w-8 text-amber-600 mb-2" />
                                    <span class="text-sm font-medium text-amber-900">Paiement</span>
                                </Link>
                                <Link :href="route('tenant.bookings.index')" class="flex flex-col items-center p-4 rounded-xl bg-rose-50 hover:bg-rose-100 transition-colors">
                                    <CalendarDaysIcon class="h-8 w-8 text-rose-600 mb-2" />
                                    <span class="text-sm font-medium text-rose-900">Réservation</span>
                                </Link>
                                <button @click="showSearch = true; showMobileQuickActions = false" class="flex flex-col items-center p-4 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                                    <MagnifyingGlassIcon class="h-8 w-8 text-gray-600 mb-2" />
                                    <span class="text-sm font-medium text-gray-900">Recherche</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
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
import NotificationCenter from '@/Components/NotificationCenter.vue'
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
    LifebuoyIcon,
    KeyIcon,
    FireIcon,
    BoltIcon,
    RocketLaunchIcon,
    AdjustmentsHorizontalIcon,
    SignalIcon,
    ChartBarSquareIcon,
    PhotoIcon,
    UserCircleIcon,
    GlobeAltIcon,
    PhoneIcon,
    ComputerDesktopIcon,
    CubeIcon,
    ShoppingCartIcon,
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
const showMobileQuickActions = ref(false)
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
        showMobileQuickActions.value = false
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

/* Slide up transition for mobile bottom sheet */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
}

.slide-up-enter-from > div:last-child,
.slide-up-leave-to > div:last-child {
    transform: translateY(100%);
}

/* Safe area padding for iOS */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 0);
}
</style>
