<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue'

const props = defineProps({
    documents: Array,
    stats: Object,
})

const activeFilter = ref('all')
const searchQuery = ref('')

const filteredDocuments = computed(() => {
    let docs = props.documents

    if (activeFilter.value !== 'all') {
        docs = docs.filter(d => d.type === activeFilter.value)
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        docs = docs.filter(d =>
            d.name.toLowerCase().includes(query) ||
            d.description.toLowerCase().includes(query)
        )
    }

    return docs
})

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        terminated: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    }
    return colors[status] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
}

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        paid: 'Payé',
        pending: 'En attente',
        expired: 'Expiré',
        terminated: 'Résilié',
        cancelled: 'Annulé',
        draft: 'Brouillon',
        pending_signature: 'Signature en attente',
    }
    return labels[status] || status
}

const getDocumentIcon = (type) => {
    if (type === 'contract') {
        return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`
    }
    return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />`
}

const getDownloadUrl = (doc) => {
    if (doc.type === 'contract') {
        return route('customer.portal.contract.download', doc.id)
    }
    return route('customer.portal.invoice.download', doc.id)
}
</script>

<template>
    <Head title="Mes documents" />

    <CustomerPortalLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes documents</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Tous vos contrats et factures au même endroit
                    </p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Contrats</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total_contracts }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-100 dark:bg-green-900/50 rounded-lg">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Actifs</p>
                                <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ stats.active_contracts }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-100 dark:bg-purple-900/50 rounded-lg">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Factures</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total_invoices }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Payées</p>
                                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.paid_invoices }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Search -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
                    <div class="p-4 flex flex-col sm:flex-row gap-4">
                        <!-- Filter Tabs -->
                        <div class="flex gap-2">
                            <button
                                @click="activeFilter = 'all'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition',
                                    activeFilter === 'all'
                                        ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                            >
                                Tous ({{ documents.length }})
                            </button>
                            <button
                                @click="activeFilter = 'contract'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition',
                                    activeFilter === 'contract'
                                        ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                            >
                                Contrats
                            </button>
                            <button
                                @click="activeFilter = 'invoice'"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition',
                                    activeFilter === 'invoice'
                                        ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                            >
                                Factures
                            </button>
                        </div>

                        <!-- Search -->
                        <div class="flex-1">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Rechercher un document..."
                                    class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div
                            v-for="doc in filteredDocuments"
                            :key="`${doc.type}-${doc.id}`"
                            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                        >
                            <div class="flex items-center gap-4">
                                <!-- Icon -->
                                <div :class="[
                                    'p-3 rounded-xl',
                                    doc.type === 'contract'
                                        ? 'bg-indigo-100 dark:bg-indigo-900/50'
                                        : 'bg-purple-100 dark:bg-purple-900/50'
                                ]">
                                    <svg
                                        :class="[
                                            'w-6 h-6',
                                            doc.type === 'contract'
                                                ? 'text-indigo-600 dark:text-indigo-400'
                                                : 'text-purple-600 dark:text-purple-400'
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                        v-html="getDocumentIcon(doc.type)"
                                    >
                                    </svg>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="font-medium text-gray-900 dark:text-white truncate">{{ doc.name }}</h3>
                                        <span :class="['px-2 py-0.5 text-xs rounded-full', getStatusColor(doc.status)]">
                                            {{ getStatusLabel(doc.status) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ doc.description }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ doc.date }}</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <Link
                                        v-if="doc.type === 'contract'"
                                        :href="route('customer.portal.contract', doc.id)"
                                        class="p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition"
                                        title="Voir"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        v-else
                                        :href="route('customer.portal.invoice', doc.id)"
                                        class="p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition"
                                        title="Voir"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <a
                                        v-if="doc.has_pdf"
                                        :href="getDownloadUrl(doc)"
                                        class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition"
                                        title="Télécharger PDF"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredDocuments.length === 0" class="p-12 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucun document trouvé</h3>
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ searchQuery ? 'Essayez avec d\'autres termes de recherche' : 'Vous n\'avez pas encore de documents' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>
