<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    CalendarDaysIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    CubeIcon,
    UserIcon,
    CurrencyEuroIcon,
    ClockIcon,
    PlusIcon,
    Cog6ToothIcon,
    CodeBracketIcon,
    TicketIcon,
    KeyIcon,
    CreditCardIcon,
    BanknotesIcon,
    BuildingLibraryIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    bookings: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const sourceFilter = ref(props.filters?.source || '')

const statuses = [
    { value: '', label: 'Tous les statuts' },
    { value: 'pending', label: 'En attente' },
    { value: 'confirmed', label: 'Confirmé' },
    { value: 'deposit_paid', label: 'Acompte payé' },
    { value: 'active', label: 'Actif' },
    { value: 'completed', label: 'Terminé' },
    { value: 'cancelled', label: 'Annulé' },
    { value: 'rejected', label: 'Refusé' },
]

const sources = [
    { value: '', label: 'Toutes les sources' },
    { value: 'website', label: 'Site web' },
    { value: 'widget', label: 'Widget' },
    { value: 'api', label: 'API' },
    { value: 'manual', label: 'Manuel' },
]

const applyFilters = () => {
    router.get(route('tenant.bookings.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        source: sourceFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-blue-100 text-blue-800',
        deposit_paid: 'bg-purple-100 text-purple-800',
        active: 'bg-green-100 text-green-800',
        completed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
        rejected: 'bg-red-100 text-red-800',
        expired: 'bg-gray-100 text-gray-600',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPaymentMethodLabel = (method) => {
    const labels = {
        'card_now': 'Carte bancaire',
        'at_signing': 'À la signature',
        'bank_transfer': 'Virement',
        'sepa_debit': 'Prélèvement SEPA',
        'cash': 'Espèces',
        'check': 'Chèque',
    }
    return labels[method] || method || 'Non défini'
}

const getPaymentMethodClass = (method) => {
    const classes = {
        'card_now': 'bg-green-100 text-green-800',
        'at_signing': 'bg-blue-100 text-blue-800',
        'bank_transfer': 'bg-purple-100 text-purple-800',
        'sepa_debit': 'bg-indigo-100 text-indigo-800',
        'cash': 'bg-orange-100 text-orange-800',
        'check': 'bg-gray-100 text-gray-800',
    }
    return classes[method] || 'bg-gray-100 text-gray-600'
}
</script>

<template>
    <TenantLayout title="Réservations en ligne">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-cyan-600 to-teal-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full -ml-48 mb-0 blur-3xl"></div>

            <div class="max-w-7xl mx-auto relative z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <CalendarDaysIcon class="h-10 w-10 text-white" />
                        <div>
                            <h1 class="text-4xl font-bold text-white">Réservations</h1>
                            <p class="mt-1 text-teal-100">Gérez les réservations en ligne</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            :href="route('tenant.bookings.settings')"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors"
                        >
                            <Cog6ToothIcon class="h-5 w-5 mr-2" />
                            Paramètres
                        </Link>
                        <Link
                            :href="route('tenant.bookings.widgets')"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors"
                        >
                            <CodeBracketIcon class="h-5 w-5 mr-2" />
                            Widgets
                        </Link>
                        <Link
                            :href="route('tenant.bookings.promo-codes')"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors"
                        >
                            <TicketIcon class="h-5 w-5 mr-2" />
                            Codes Promo
                        </Link>
                        <Link
                            :href="route('tenant.bookings.api-keys')"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-colors"
                        >
                            <KeyIcon class="h-5 w-5 mr-2" />
                            Clés API
                        </Link>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="text-teal-100 text-sm">Total</div>
                        <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="text-teal-100 text-sm">En attente</div>
                        <div class="text-2xl font-bold text-yellow-300">{{ stats.pending }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="text-teal-100 text-sm">Confirmées</div>
                        <div class="text-2xl font-bold text-blue-300">{{ stats.confirmed }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="text-teal-100 text-sm">Actives</div>
                        <div class="text-2xl font-bold text-green-300">{{ stats.active }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4">
                        <div class="text-teal-100 text-sm">Ce mois</div>
                        <div class="text-2xl font-bold text-white">{{ stats.this_month }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>
                    <select
                        v-model="statusFilter"
                        class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
                        @change="applyFilters"
                    >
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <select
                        v-model="sourceFilter"
                        class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500"
                        @change="applyFilters"
                    >
                        <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors"
                    >
                        <FunnelIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="bookings.data.length === 0" class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <CalendarDaysIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune réservation</h3>
                <p class="text-gray-500">Les réservations en ligne apparaîtront ici</p>
            </div>

            <!-- Bookings Table -->
            <div v-else class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Réservation</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Client</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Box</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Date début</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Prix/mois</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Paiement</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Source</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Statut</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="booking in bookings.data"
                                :key="booking.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ booking.booking_number }}</div>
                                    <div class="text-sm text-gray-500">{{ booking.created_at }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <UserIcon class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <div class="font-medium text-gray-900">{{ booking.customer_name }}</div>
                                            <div class="text-sm text-gray-500">{{ booking.customer_email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <CubeIcon class="h-4 w-4 text-gray-400" />
                                        <div>
                                            <div class="font-medium text-gray-900">{{ booking.box_name }}</div>
                                            <div class="text-sm text-gray-500">{{ booking.site_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <ClockIcon class="h-4 w-4 text-gray-400" />
                                        <span class="text-gray-900">{{ booking.start_date }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                                        <span class="font-medium text-gray-900">{{ formatCurrency(booking.monthly_price) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="getPaymentMethodClass(booking.payment_method)"
                                        class="px-2 py-1 rounded-full text-xs font-medium inline-flex items-center"
                                    >
                                        <CreditCardIcon v-if="booking.payment_method === 'card_now'" class="h-3 w-3 mr-1" />
                                        <BanknotesIcon v-else-if="booking.payment_method === 'cash'" class="h-3 w-3 mr-1" />
                                        <BuildingLibraryIcon v-else-if="booking.payment_method === 'bank_transfer'" class="h-3 w-3 mr-1" />
                                        {{ getPaymentMethodLabel(booking.payment_method) }}
                                    </span>
                                    <div v-if="booking.amount_paid > 0" class="text-xs text-green-600 mt-1">
                                        ✓ Payé: {{ formatCurrency(booking.amount_paid) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600">{{ booking.source_label }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="getStatusClass(booking.status)"
                                        class="px-3 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ booking.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <Link
                                        :href="route('tenant.bookings.show', booking.id)"
                                        class="inline-flex items-center px-3 py-1.5 bg-teal-50 text-teal-700 rounded-lg hover:bg-teal-100 transition-colors"
                                    >
                                        <EyeIcon class="h-4 w-4 mr-1" />
                                        Voir
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="bookings.links && bookings.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Affichage de {{ bookings.from }} à {{ bookings.to }} sur {{ bookings.total }} résultats
                        </div>
                        <div class="flex items-center space-x-2">
                            <template v-for="link in bookings.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-3 py-1 rounded-lg text-sm"
                                    :class="link.active ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1 text-sm text-gray-400"
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
