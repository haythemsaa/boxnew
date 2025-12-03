<template>
    <AuthenticatedLayout title="Clients">
        <!-- Message de succès avec animation -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-emerald-800">{{ $page.props.flash.success }}</p>
            </div>
        </transition>

        <!-- En-tête moderne avec gradient -->
        <div class="relative mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                        Gestion des Clients
                    </h1>
                    <p class="mt-1 text-gray-500">Gérez votre base de clients et suivez leur activité</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a
                        :href="route('tenant.customers.export')"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm"
                    >
                        <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exporter
                    </a>
                    <Link
                        :href="route('tenant.customers.create')"
                        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-xl text-sm font-semibold shadow-lg shadow-primary-500/25 transition-all duration-200 hover:shadow-xl hover:shadow-primary-500/30 hover:-translate-y-0.5"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouveau Client
                    </Link>
                </div>
            </div>
        </div>

        <!-- Cartes statistiques modernes -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <!-- Total -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl shadow-lg shadow-violet-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Actifs -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Actifs</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.active }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl shadow-lg shadow-emerald-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Particuliers -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Particuliers</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.individual }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg shadow-blue-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Entreprises -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Entreprises</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.company }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg shadow-amber-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Chiffre d'affaires -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300 col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">CA Mensuel</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(stats.total_revenue || 0) }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl shadow-lg shadow-indigo-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de filtres moderne -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Recherche -->
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher par nom, email, téléphone..."
                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                        @input="handleSearch"
                    />
                </div>

                <!-- Filtres -->
                <div class="flex flex-wrap gap-3">
                    <select
                        v-model="typeFilter"
                        class="px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 min-w-[160px]"
                        @change="handleFilterChange"
                    >
                        <option value="">Tous les types</option>
                        <option value="individual">Particulier</option>
                        <option value="company">Entreprise</option>
                    </select>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 min-w-[160px]"
                        @change="handleFilterChange"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                        <option value="suspended">Suspendu</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tableau moderne -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Contrats
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <!-- État vide -->
                        <tr v-if="customers.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="p-4 bg-gray-100 rounded-full mb-4">
                                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Aucun client trouvé</h3>
                                    <p class="text-sm text-gray-500 mb-6 max-w-sm">
                                        {{ filters.search || filters.type || filters.status ? 'Essayez de modifier vos filtres' : 'Commencez par ajouter votre premier client' }}
                                    </p>
                                    <Link
                                        v-if="!filters.search && !filters.type && !filters.status"
                                        :href="route('tenant.customers.create')"
                                        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-primary-500/25 hover:shadow-xl transition-all duration-200"
                                    >
                                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Ajouter un client
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Lignes de données -->
                        <tr
                            v-else
                            v-for="customer in customers.data"
                            :key="customer.id"
                            class="hover:bg-gray-50/50 transition-colors duration-150"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center text-white font-semibold text-sm"
                                        :class="customer.type === 'company' ? 'bg-gradient-to-br from-amber-500 to-orange-600' : 'bg-gradient-to-br from-blue-500 to-cyan-600'"
                                    >
                                        {{ getInitials(customer) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}` }}
                                        </p>
                                        <p v-if="customer.civility && customer.type === 'individual'" class="text-xs text-gray-500">
                                            {{ getCivilityLabel(customer.civility) }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        {{ customer.email }}
                                    </div>
                                    <div v-if="customer.phone" class="flex items-center gap-2 text-sm text-gray-500">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ customer.phone }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium"
                                    :class="{
                                        'bg-blue-50 text-blue-700': customer.type === 'individual',
                                        'bg-amber-50 text-amber-700': customer.type === 'company'
                                    }"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full mr-1.5"
                                        :class="{
                                            'bg-blue-500': customer.type === 'individual',
                                            'bg-amber-500': customer.type === 'company'
                                        }"
                                    ></span>
                                    {{ customer.type === 'individual' ? 'Particulier' : 'Entreprise' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900">{{ customer.contracts_count || 0 }}</span>
                                    <span class="text-xs text-gray-500">contrat{{ (customer.contracts_count || 0) > 1 ? 's' : '' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium"
                                    :class="getStatusClasses(customer.status)"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full mr-1.5"
                                        :class="getStatusDotClass(customer.status)"
                                    ></span>
                                    {{ getStatusLabel(customer.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        :href="route('tenant.customers.show', customer.id)"
                                        class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                                        title="Voir"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        :href="route('tenant.customers.edit', customer.id)"
                                        class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                        title="Modifier"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="confirmDelete(customer)"
                                        class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Supprimer"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination moderne -->
            <div v-if="customers.data.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">
                        Affichage de <span class="font-semibold text-gray-900">{{ customers.from }}</span>
                        à <span class="font-semibold text-gray-900">{{ customers.to }}</span>
                        sur <span class="font-semibold text-gray-900">{{ customers.total }}</span> résultats
                    </p>
                    <div class="flex items-center gap-2">
                        <template v-for="link in customers.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3.5 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                                    link.active
                                        ? 'bg-primary-600 text-white shadow-sm'
                                        : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 hover:border-gray-300'
                                ]"
                                :preserve-scroll="true"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3.5 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de suppression moderne -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeDeleteModal"></div>

                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div v-if="showDeleteModal" class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:max-w-lg sm:w-full">
                            <div class="p-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 p-3 bg-red-100 rounded-full">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Supprimer le client</h3>
                                        <p class="mt-2 text-sm text-gray-500">
                                            Êtes-vous sûr de vouloir supprimer
                                            <strong class="text-gray-900">{{ customerToDelete ? (customerToDelete.type === 'company' ? customerToDelete.company_name : `${customerToDelete.first_name} ${customerToDelete.last_name}`) : '' }}</strong> ?
                                            Cette action est irréversible.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                                <button
                                    @click="deleteCustomer"
                                    :disabled="deleteForm.processing"
                                    class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ deleteForm.processing ? 'Suppression...' : 'Supprimer' }}
                                </button>
                                <button
                                    @click="closeDeleteModal"
                                    :disabled="deleteForm.processing"
                                    class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors disabled:opacity-50"
                                >
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    customers: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const typeFilter = ref(props.filters.type || '')
const statusFilter = ref(props.filters.status || '')

const showDeleteModal = ref(false)
const customerToDelete = ref(null)
const deleteForm = useForm({})

let searchTimeout = null

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.customers.index'), {
            search: search.value,
            type: typeFilter.value,
            status: statusFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.customers.index'), {
        search: search.value,
        type: typeFilter.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const confirmDelete = (customer) => {
    customerToDelete.value = customer
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    customerToDelete.value = null
}

const deleteCustomer = () => {
    deleteForm.delete(route('tenant.customers.destroy', customerToDelete.value.id), {
        onSuccess: () => {
            closeDeleteModal()
        },
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount)
}

const getInitials = (customer) => {
    if (customer.type === 'company') {
        return customer.company_name?.substring(0, 2).toUpperCase() || 'EN'
    }
    return `${customer.first_name?.charAt(0) || ''}${customer.last_name?.charAt(0) || ''}`.toUpperCase()
}

const getCivilityLabel = (civility) => {
    const labels = {
        mr: 'Monsieur',
        mrs: 'Madame',
        ms: 'Mademoiselle',
        dr: 'Docteur',
        prof: 'Professeur',
    }
    return labels[civility] || civility
}

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        inactive: 'Inactif',
        suspended: 'Suspendu',
    }
    return labels[status] || status
}

const getStatusClasses = (status) => {
    const classes = {
        active: 'bg-emerald-50 text-emerald-700',
        inactive: 'bg-gray-100 text-gray-600',
        suspended: 'bg-red-50 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-600'
}

const getStatusDotClass = (status) => {
    const classes = {
        active: 'bg-emerald-500',
        inactive: 'bg-gray-400',
        suspended: 'bg-red-500',
    }
    return classes[status] || 'bg-gray-400'
}
</script>
