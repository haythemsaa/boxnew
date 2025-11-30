<script setup>
import { ref, watch } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    prospects: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const sourceFilter = ref(props.filters.source || '')

const showDeleteModal = ref(false)
const prospectToDelete = ref(null)
const showContactModal = ref(false)
const prospectToContact = ref(null)
const deleteForm = useForm({})
const contactForm = useForm({
    notes: '',
    status: '',
})

let searchTimeout = null

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.prospects.index'), {
            search: search.value,
            status: statusFilter.value,
            source: sourceFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.prospects.index'), {
        search: search.value,
        status: statusFilter.value,
        source: sourceFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const confirmDelete = (prospect) => {
    prospectToDelete.value = prospect
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    prospectToDelete.value = null
}

const deleteProspect = () => {
    deleteForm.delete(route('tenant.prospects.destroy', prospectToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    })
}

const openContactModal = (prospect) => {
    prospectToContact.value = prospect
    contactForm.notes = ''
    contactForm.status = prospect.status
    showContactModal.value = true
}

const closeContactModal = () => {
    showContactModal.value = false
    prospectToContact.value = null
}

const recordContact = () => {
    contactForm.post(route('tenant.prospects.record-contact', prospectToContact.value.id), {
        onSuccess: () => closeContactModal(),
    })
}

const convertToCustomer = (prospect) => {
    if (confirm('Voulez-vous convertir ce prospect en client ?')) {
        router.post(route('tenant.prospects.convert', prospect.id))
    }
}

const markAsLost = (prospect) => {
    if (confirm('Voulez-vous marquer ce prospect comme perdu ?')) {
        router.post(route('tenant.prospects.mark-lost', prospect.id))
    }
}

const getStatusLabel = (status) => {
    const labels = {
        new: 'Nouveau',
        contacted: 'Contacté',
        qualified: 'Qualifié',
        quoted: 'Devis envoyé',
        converted: 'Converti',
        lost: 'Perdu',
    }
    return labels[status] || status
}

const getStatusColor = (status) => {
    const colors = {
        new: 'bg-blue-100 text-blue-800',
        contacted: 'bg-yellow-100 text-yellow-800',
        qualified: 'bg-purple-100 text-purple-800',
        quoted: 'bg-indigo-100 text-indigo-800',
        converted: 'bg-green-100 text-green-800',
        lost: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getSourceLabel = (source) => {
    const labels = {
        website: 'Site web',
        phone: 'Téléphone',
        email: 'Email',
        referral: 'Recommandation',
        walk_in: 'Visite',
        social_media: 'Réseaux sociaux',
        other: 'Autre',
    }
    return labels[source] || source
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
    if (!amount) return '-'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount)
}
</script>

<template>
    <AuthenticatedLayout title="Prospects">
        <!-- Success/Error Messages -->
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-600">{{ $page.props.flash.success }}</p>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ $page.props.flash.error }}</p>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Prospects</h2>
                <p class="mt-1 text-sm text-gray-500">Gérez vos prospects et convertissez-les en clients</p>
            </div>
            <Link
                :href="route('tenant.prospects.create')"
                class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau Prospect
            </Link>
        </div>

        <!-- Stats Pipeline -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Pipeline des prospects</h3>
            <div class="grid gap-4 md:grid-cols-7">
                <div class="text-center p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                    <p class="text-3xl font-bold text-blue-600">{{ stats.new }}</p>
                    <p class="text-sm text-blue-700 font-medium">Nouveaux</p>
                </div>
                <div class="text-center p-4 bg-yellow-50 rounded-lg border-2 border-yellow-200">
                    <p class="text-3xl font-bold text-yellow-600">{{ stats.contacted }}</p>
                    <p class="text-sm text-yellow-700 font-medium">Contactés</p>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg border-2 border-purple-200">
                    <p class="text-3xl font-bold text-purple-600">{{ stats.qualified }}</p>
                    <p class="text-sm text-purple-700 font-medium">Qualifiés</p>
                </div>
                <div class="text-center p-4 bg-indigo-50 rounded-lg border-2 border-indigo-200">
                    <p class="text-3xl font-bold text-indigo-600">{{ stats.quoted }}</p>
                    <p class="text-sm text-indigo-700 font-medium">Devis envoyé</p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg border-2 border-green-200">
                    <p class="text-3xl font-bold text-green-600">{{ stats.converted }}</p>
                    <p class="text-sm text-green-700 font-medium">Convertis</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                    <p class="text-3xl font-bold text-gray-600">{{ stats.lost }}</p>
                    <p class="text-sm text-gray-700 font-medium">Perdus</p>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg border-2 border-red-200">
                    <p class="text-3xl font-bold text-red-600">{{ stats.hot }}</p>
                    <p class="text-sm text-red-700 font-medium">Chauds</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid gap-4 md:grid-cols-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un prospect..."
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @input="handleSearch"
                />
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @change="handleFilterChange"
                >
                    <option value="">Tous les statuts</option>
                    <option value="new">Nouveau</option>
                    <option value="contacted">Contacté</option>
                    <option value="qualified">Qualifié</option>
                    <option value="quoted">Devis envoyé</option>
                    <option value="converted">Converti</option>
                    <option value="lost">Perdu</option>
                </select>
                <select
                    v-model="sourceFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @change="handleFilterChange"
                >
                    <option value="">Toutes les sources</option>
                    <option value="website">Site web</option>
                    <option value="phone">Téléphone</option>
                    <option value="email">Email</option>
                    <option value="referral">Recommandation</option>
                    <option value="walk_in">Visite</option>
                    <option value="social_media">Réseaux sociaux</option>
                    <option value="other">Autre</option>
                </select>
            </div>
        </div>

        <!-- Prospects Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prospect
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Intérêt
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Source
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Relances
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="prospects.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Aucun prospect trouvé</p>
                                    <p class="text-sm text-gray-500 mb-4">Commencez par créer votre premier prospect</p>
                                    <Link
                                        :href="route('tenant.prospects.create')"
                                        class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                    >
                                        + Nouveau Prospect
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="prospect in prospects.data" :key="prospect.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ prospect.type === 'company' ? prospect.company_name : `${prospect.first_name} ${prospect.last_name}` }}
                                    </div>
                                    <div v-if="prospect.type === 'company'" class="text-sm text-gray-500">
                                        {{ prospect.first_name }} {{ prospect.last_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ prospect.email }}</div>
                                <div v-if="prospect.phone" class="text-sm text-gray-500">{{ prospect.phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span v-if="prospect.box_size_interested" class="font-medium">{{ prospect.box_size_interested }}</span>
                                    <span v-else class="text-gray-400">-</span>
                                </div>
                                <div v-if="prospect.move_in_date" class="text-sm text-gray-500">
                                    Entrée: {{ formatDate(prospect.move_in_date) }}
                                </div>
                                <div v-if="prospect.budget" class="text-sm text-gray-500">
                                    Budget: {{ formatCurrency(prospect.budget) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">{{ getSourceLabel(prospect.source) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="getStatusColor(prospect.status)"
                                >
                                    {{ getStatusLabel(prospect.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ prospect.follow_up_count }} relances</div>
                                <div v-if="prospect.last_contact_at" class="text-xs text-gray-500">
                                    Dernier: {{ formatDate(prospect.last_contact_at) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button
                                        v-if="prospect.status !== 'converted' && prospect.status !== 'lost'"
                                        @click="openContactModal(prospect)"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="Enregistrer un contact"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="prospect.status !== 'converted' && prospect.status !== 'lost'"
                                        @click="convertToCustomer(prospect)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Convertir en client"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <Link
                                        :href="route('tenant.prospects.edit', prospect.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                        title="Modifier"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        v-if="prospect.status !== 'converted' && prospect.status !== 'lost'"
                                        @click="markAsLost(prospect)"
                                        class="text-gray-600 hover:text-gray-900"
                                        title="Marquer comme perdu"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(prospect)"
                                        class="text-red-600 hover:text-red-900"
                                        title="Supprimer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="prospects.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Affichage de <span class="font-medium">{{ prospects.from }}</span> à <span class="font-medium">{{ prospects.to }}</span> sur <span class="font-medium">{{ prospects.total }}</span> résultats
                    </div>
                    <div class="flex space-x-2">
                        <Link
                            v-for="link in prospects.links"
                            :key="link.label"
                            :href="link.url"
                            :class="{
                                'px-4 py-2 border rounded-lg transition-colors': true,
                                'bg-primary-600 text-white border-primary-600': link.active,
                                'bg-white text-gray-700 border-gray-300 hover:bg-gray-50': !link.active && link.url,
                                'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed': !link.url
                            }"
                            :preserve-scroll="true"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeDeleteModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Supprimer le prospect</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Êtes-vous sûr de vouloir supprimer ce prospect ? Cette action est irréversible.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteProspect"
                            :disabled="deleteForm.processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            {{ deleteForm.processing ? 'Suppression...' : 'Supprimer' }}
                        </button>
                        <button
                            @click="closeDeleteModal"
                            :disabled="deleteForm.processing"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Modal -->
        <div v-if="showContactModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeContactModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Enregistrer un contact</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau statut</label>
                                <select
                                    v-model="contactForm.status"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                >
                                    <option value="new">Nouveau</option>
                                    <option value="contacted">Contacté</option>
                                    <option value="qualified">Qualifié</option>
                                    <option value="quoted">Devis envoyé</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea
                                    v-model="contactForm.notes"
                                    rows="4"
                                    placeholder="Résumé de l'échange..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="recordContact"
                            :disabled="contactForm.processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            {{ contactForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                        <button
                            @click="closeContactModal"
                            :disabled="contactForm.processing"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
