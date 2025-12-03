<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    BellAlertIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    PaperAirplaneIcon,
    EnvelopeIcon,
    DocumentTextIcon,
    CurrencyEuroIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XMarkIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    reminders: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const levelFilter = ref(props.filters?.level || '')
const selectedReminders = ref([])

let searchTimeout = null

const statusOptions = [
    { value: 'pending', label: 'En attente', icon: '⏳', color: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
    { value: 'sent', label: 'Envoyée', icon: '📤', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    { value: 'paid', label: 'Payée', icon: '✅', color: 'bg-green-100 text-green-700 border-green-200' },
    { value: 'cancelled', label: 'Annulée', icon: '❌', color: 'bg-gray-100 text-gray-600 border-gray-200' },
]

const levelOptions = [
    { value: 1, label: 'Relance 1', icon: '1️⃣', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    { value: 2, label: 'Relance 2', icon: '2️⃣', color: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
    { value: 3, label: 'Relance 3', icon: '3️⃣', color: 'bg-orange-100 text-orange-700 border-orange-200' },
    { value: 4, label: 'Mise en demeure', icon: '⚠️', color: 'bg-red-100 text-red-700 border-red-200' },
]

const getStatusConfig = (status) => {
    return statusOptions.find(s => s.value === status) || statusOptions[0]
}

const getLevelConfig = (level) => {
    return levelOptions.find(l => l.value === level) || levelOptions[0]
}

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.reminders.index'), {
            search: search.value,
            status: statusFilter.value,
            level: levelFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.reminders.index'), {
        search: search.value,
        status: statusFilter.value,
        level: levelFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    search.value = ''
    statusFilter.value = ''
    levelFilter.value = ''
    router.get(route('tenant.reminders.index'))
}

const hasActiveFilters = computed(() => {
    return search.value || statusFilter.value || levelFilter.value
})

const sendReminder = (reminder) => {
    router.post(route('tenant.reminders.send', reminder.id))
}

const sendBulkReminders = () => {
    if (selectedReminders.value.length === 0) {
        alert('Veuillez sélectionner au moins une relance.')
        return
    }
    router.post(route('tenant.reminders.send-bulk'), {
        reminder_ids: selectedReminders.value,
    })
}

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedReminders.value = props.reminders.data
            .filter(r => r.status === 'pending')
            .map(r => r.id)
    } else {
        selectedReminders.value = []
    }
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
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}

const getTypeIcon = (type) => {
    switch(type) {
        case 'email': return '📧'
        case 'sms': return '📱'
        case 'letter': return '📬'
        default: return '📧'
    }
}

const getTypeLabel = (type) => {
    switch(type) {
        case 'email': return 'Email'
        case 'sms': return 'SMS'
        case 'letter': return 'Courrier'
        default: return type
    }
}
</script>

<template>
    <TenantLayout title="Relances">
        <div class="space-y-6">
            <!-- Header avec gradient -->
            <div class="relative overflow-hidden bg-gradient-to-br from-rose-500 via-pink-500 to-red-500 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <BellAlertIcon class="h-8 w-8 text-white" />
                                </div>
                                Relances de Paiement
                            </h1>
                            <p class="mt-2 text-rose-100">
                                Gérez les relances pour les factures impayées
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button
                                v-if="selectedReminders.length > 0"
                                @click="sendBulkReminders"
                                class="inline-flex items-center px-5 py-3 bg-yellow-500 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                            >
                                <PaperAirplaneIcon class="h-5 w-5 mr-2" />
                                Envoyer ({{ selectedReminders.length }})
                            </button>
                            <Link
                                :href="route('tenant.reminders.overdue-invoices')"
                                class="inline-flex items-center px-6 py-3 bg-white text-rose-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                            >
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Nouvelle Relance
                            </Link>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-6 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.total || 0 }}</p>
                            <p class="text-xs text-rose-100 font-medium mt-1">Total</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.pending || 0 }}</p>
                            <p class="text-xs text-rose-100 font-medium mt-1">En attente</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.sent || 0 }}</p>
                            <p class="text-xs text-rose-100 font-medium mt-1">Envoyées</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.paid || 0 }}</p>
                            <p class="text-xs text-rose-100 font-medium mt-1">Payées</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.overdue_invoices || 0 }}</p>
                            <p class="text-xs text-rose-100 font-medium mt-1">Factures impayées</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ formatCurrency(stats?.total_overdue || 0) }}</p>
                            <p class="text-xs text-rose-100 font-medium mt-1">Montant impayé</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Recherche -->
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher par facture, client..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Filtre Statut -->
                    <div class="w-full lg:w-44">
                        <select
                            v-model="statusFilter"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all bg-white"
                            @change="handleFilterChange"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.icon }} {{ status.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtre Niveau -->
                    <div class="w-full lg:w-44">
                        <select
                            v-model="levelFilter"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all bg-white"
                            @change="handleFilterChange"
                        >
                            <option value="">Tous les niveaux</option>
                            <option v-for="level in levelOptions" :key="level.value" :value="level.value">
                                {{ level.icon }} {{ level.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Bouton Reset -->
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center px-4 py-2.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors"
                    >
                        <ArrowPathIcon class="h-5 w-5 mr-2" />
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Liste des relances -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- État vide -->
                <div v-if="!reminders?.data?.length" class="py-16 px-4 text-center">
                    <div class="mx-auto w-16 h-16 bg-rose-100 rounded-full flex items-center justify-center mb-4">
                        <BellAlertIcon class="h-8 w-8 text-rose-500" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune relance</h3>
                    <p class="text-gray-500 mb-6">Créez une relance pour les factures impayées</p>
                    <Link
                        :href="route('tenant.reminders.overdue-invoices')"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-rose-500 to-pink-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Créer une relance
                    </Link>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <input
                                        type="checkbox"
                                        @change="toggleSelectAll"
                                        class="h-4 w-4 text-rose-600 focus:ring-rose-500 border-gray-300 rounded"
                                    />
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Facture
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Client
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Montant dû
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Niveau
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <tr
                                v-for="(reminder, index) in reminders.data"
                                :key="reminder.id"
                                class="hover:bg-rose-50/50 transition-colors duration-150"
                                :style="{ animationDelay: `${index * 50}ms` }"
                            >
                                <td class="px-6 py-4">
                                    <input
                                        v-if="reminder.status === 'pending'"
                                        type="checkbox"
                                        :value="reminder.id"
                                        v-model="selectedReminders"
                                        class="h-4 w-4 text-rose-600 focus:ring-rose-500 border-gray-300 rounded"
                                    />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <DocumentTextIcon class="h-5 w-5 text-gray-400" />
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ reminder.invoice?.invoice_number || '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ getCustomerName(reminder.invoice?.customer) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-red-600">
                                        {{ formatCurrency(reminder.amount_due) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                        :class="getLevelConfig(reminder.level).color"
                                    >
                                        {{ getLevelConfig(reminder.level).icon }}
                                        {{ getLevelConfig(reminder.level).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                                        {{ getTypeIcon(reminder.type) }}
                                        {{ getTypeLabel(reminder.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                        :class="getStatusConfig(reminder.status).color"
                                    >
                                        {{ getStatusConfig(reminder.status).icon }}
                                        {{ getStatusConfig(reminder.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1 text-sm text-gray-500">
                                        <ClockIcon class="h-4 w-4" />
                                        {{ formatDate(reminder.sent_at || reminder.scheduled_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button
                                        v-if="reminder.status === 'pending'"
                                        @click="sendReminder(reminder)"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Envoyer"
                                    >
                                        <PaperAirplaneIcon class="h-5 w-5" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reminders?.data?.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-600">
                            Affichage de <span class="font-semibold">{{ reminders.from }}</span> à
                            <span class="font-semibold">{{ reminders.to }}</span> sur
                            <span class="font-semibold">{{ reminders.total }}</span> résultats
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in reminders.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-lg transition-all',
                                        link.active
                                            ? 'bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-sm'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    ]"
                                    :preserve-scroll="true"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
