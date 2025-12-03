<template>
    <TenantLayout title="Contrats">
        <!-- Message de succès -->
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

        <!-- En-tête moderne -->
        <div class="relative mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                        Gestion des Contrats
                    </h1>
                    <p class="mt-1 text-gray-500">Gérez vos contrats de location de boxes</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a
                        :href="route('tenant.contracts.export', { status: status })"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm"
                    >
                        <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exporter
                    </a>
                    <Link
                        :href="route('tenant.contracts.create-wizard')"
                        class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-emerald-500/25 transition-all duration-200 hover:shadow-xl"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        Assistant
                    </Link>
                    <Link
                        :href="route('tenant.contracts.create')"
                        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-xl text-sm font-semibold shadow-lg shadow-primary-500/25 transition-all duration-200 hover:shadow-xl hover:-translate-y-0.5"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouveau Contrat
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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

            <!-- En attente -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">En attente</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.pending }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg shadow-amber-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Expirés -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Expirés</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.expired }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl shadow-lg shadow-red-500/25 group-hover:scale-110 transition-transform duration-300">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Revenus mensuels -->
            <div class="group bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-lg hover:border-gray-200 transition-all duration-300 col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">CA Mensuel</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(stats.total_revenue) }}</p>
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
                        placeholder="Rechercher par n° contrat, client, box..."
                        class="w-full pl-11 pr-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                    />
                </div>

                <!-- Filtres -->
                <div class="flex flex-wrap gap-3">
                    <select
                        v-model="status"
                        class="px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 min-w-[160px]"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="draft">Brouillon</option>
                        <option value="pending_signature">En attente signature</option>
                        <option value="active">Actif</option>
                        <option value="expired">Expiré</option>
                        <option value="terminated">Résilié</option>
                        <option value="cancelled">Annulé</option>
                    </select>
                    <select
                        v-model="type"
                        class="px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 min-w-[140px]"
                    >
                        <option value="">Tous les types</option>
                        <option value="standard">Standard</option>
                        <option value="short_term">Court terme</option>
                        <option value="long_term">Long terme</option>
                    </select>
                    <select
                        v-model="siteId"
                        class="px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm text-gray-700 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 min-w-[140px]"
                    >
                        <option value="">Tous les sites</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                            {{ site.name }}
                        </option>
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
                                Contrat
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Box / Site
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Dates
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Loyer
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <!-- État vide -->
                        <tr v-if="contracts.data.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="p-4 bg-gray-100 rounded-full mb-4">
                                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Aucun contrat trouvé</h3>
                                    <p class="text-sm text-gray-500 mb-6 max-w-sm">
                                        {{ search || status || type || siteId ? 'Essayez de modifier vos filtres' : 'Commencez par créer votre premier contrat' }}
                                    </p>
                                    <Link
                                        v-if="!search && !status && !type && !siteId"
                                        :href="route('tenant.contracts.create')"
                                        class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-primary-500/25 hover:shadow-xl transition-all duration-200"
                                    >
                                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Créer un contrat
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Lignes de données -->
                        <tr
                            v-else
                            v-for="contract in contracts.data"
                            :key="contract.id"
                            class="hover:bg-gray-50/50 transition-colors duration-150"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ contract.contract_number }}</p>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                            :class="getTypeClasses(contract.type)"
                                        >
                                            {{ getTypeLabel(contract.type) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center text-white font-semibold text-xs"
                                        :class="contract.customer?.type === 'company' ? 'bg-gradient-to-br from-amber-500 to-orange-600' : 'bg-gradient-to-br from-blue-500 to-cyan-600'"
                                    >
                                        {{ getCustomerInitials(contract.customer) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ getCustomerName(contract.customer) }}</p>
                                        <p v-if="contract.customer?.email" class="text-xs text-gray-500">{{ contract.customer.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span class="text-sm font-medium text-gray-900">{{ contract.box?.code || '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="text-xs text-gray-500">{{ contract.site?.name || '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium"
                                    :class="getStatusClasses(contract.status)"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full mr-1.5"
                                        :class="getStatusDotClass(contract.status)"
                                    ></span>
                                    {{ getStatusLabel(contract.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-500">Début:</span>
                                        <span class="font-medium text-gray-900">{{ formatDate(contract.start_date) }}</span>
                                    </div>
                                    <div v-if="contract.end_date" class="flex items-center gap-2">
                                        <span class="text-gray-500">Fin:</span>
                                        <span class="font-medium text-gray-900">{{ formatDate(contract.end_date) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-gray-900">{{ formatCurrency(contract.monthly_price) }}</p>
                                <p class="text-xs text-gray-500">/mois</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        :href="route('tenant.contracts.show', contract.id)"
                                        class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                                        title="Voir"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        :href="route('tenant.contracts.edit', contract.id)"
                                        class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                        title="Modifier"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="confirmDelete(contract)"
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
            <div v-if="contracts.data.length > 0 && contracts.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-sm text-gray-600">
                        Affichage de <span class="font-semibold text-gray-900">{{ contracts.from }}</span>
                        à <span class="font-semibold text-gray-900">{{ contracts.to }}</span>
                        sur <span class="font-semibold text-gray-900">{{ contracts.total }}</span> résultats
                    </p>
                    <div class="flex items-center gap-2">
                        <template v-for="(link, index) in contracts.links" :key="index">
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
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>

                    <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:max-w-lg sm:w-full">
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 p-3 bg-red-100 rounded-full">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Supprimer le contrat</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Êtes-vous sûr de vouloir supprimer le contrat
                                        <strong class="text-gray-900">{{ contractToDelete?.contract_number }}</strong> ?
                                        Cette action est irréversible.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                            <button
                                @click="deleteContract"
                                class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors"
                            >
                                Supprimer
                            </button>
                            <button
                                @click="showDeleteModal = false"
                                class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors"
                            >
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </TenantLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    contracts: Object,
    stats: Object,
    sites: Array,
    filters: Object,
})

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')
const type = ref(props.filters.type || '')
const siteId = ref(props.filters.site_id || '')

const showDeleteModal = ref(false)
const contractToDelete = ref(null)

const updateFilters = () => {
    router.get(
        route('tenant.contracts.index'),
        {
            search: search.value,
            status: status.value,
            type: type.value,
            site_id: siteId.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}

watch([search], () => {
    const timer = setTimeout(() => {
        updateFilters()
    }, 300)
    return () => clearTimeout(timer)
})

watch([status, type, siteId], () => {
    updateFilters()
})

const confirmDelete = (contract) => {
    contractToDelete.value = contract
    showDeleteModal.value = true
}

const deleteContract = () => {
    if (contractToDelete.value) {
        router.delete(route('tenant.contracts.destroy', contractToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false
                contractToDelete.value = null
            },
        })
    }
}

const getStatusClasses = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700',
        pending_signature: 'bg-amber-50 text-amber-700',
        active: 'bg-emerald-50 text-emerald-700',
        expired: 'bg-red-50 text-red-700',
        terminated: 'bg-red-50 text-red-700',
        cancelled: 'bg-gray-100 text-gray-600',
    }
    return classes[status] || 'bg-gray-100 text-gray-600'
}

const getStatusDotClass = (status) => {
    const classes = {
        draft: 'bg-gray-400',
        pending_signature: 'bg-amber-500',
        active: 'bg-emerald-500',
        expired: 'bg-red-500',
        terminated: 'bg-red-500',
        cancelled: 'bg-gray-400',
    }
    return classes[status] || 'bg-gray-400'
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        pending_signature: 'En attente',
        active: 'Actif',
        expired: 'Expiré',
        terminated: 'Résilié',
        cancelled: 'Annulé',
    }
    return labels[status] || status
}

const getTypeClasses = (type) => {
    const classes = {
        standard: 'bg-blue-100 text-blue-700',
        short_term: 'bg-purple-100 text-purple-700',
        long_term: 'bg-indigo-100 text-indigo-700',
    }
    return classes[type] || 'bg-gray-100 text-gray-700'
}

const getTypeLabel = (type) => {
    const labels = {
        standard: 'Standard',
        short_term: 'Court terme',
        long_term: 'Long terme',
    }
    return labels[type] || type
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getCustomerInitials = (customer) => {
    if (!customer) return '?'
    if (customer.type === 'company') {
        return customer.company_name?.substring(0, 2).toUpperCase() || 'EN'
    }
    return `${customer.first_name?.charAt(0) || ''}${customer.last_name?.charAt(0) || ''}`.toUpperCase()
}
</script>
