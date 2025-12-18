<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    accessCodes: Object,
    sites: Array,
    filters: Object,
})

const showCreateModal = ref(false)
const selectedCode = ref(null)

const form = useForm({
    customer_id: '',
    site_id: '',
    contract_id: '',
    is_permanent: false,
    is_master: false,
    valid_from: '',
    valid_until: '',
    max_uses: null,
})

const createCode = () => {
    form.post(route('tenant.self-service.access-codes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        }
    })
}

const toggleCode = (code) => {
    router.post(route('tenant.self-service.access-codes.toggle', code.id), {}, {
        preserveScroll: true,
    })
}

const revokeCode = (code) => {
    if (confirm('Êtes-vous sûr de vouloir révoquer ce code ?')) {
        router.post(route('tenant.self-service.access-codes.revoke', code.id), {}, {
            preserveScroll: true,
        })
    }
}

const regenerateCode = (code) => {
    if (confirm('Êtes-vous sûr de vouloir régénérer ce code ? L\'ancien code ne fonctionnera plus.')) {
        router.post(route('tenant.self-service.access-codes.regenerate', code.id), {}, {
            preserveScroll: true,
        })
    }
}

const applyFilters = () => {
    router.get(route('tenant.self-service.access-codes'), props.filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    router.get(route('tenant.self-service.access-codes'))
}

const statusLabel = (status) => {
    const labels = {
        active: 'Actif',
        suspended: 'Suspendu',
        expired: 'Expiré',
        revoked: 'Révoqué'
    }
    return labels[status] || status
}

const statusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        suspended: 'bg-yellow-100 text-yellow-800',
        expired: 'bg-gray-100 text-gray-800',
        revoked: 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const copyToClipboard = async (text) => {
    await navigator.clipboard.writeText(text)
    alert('Code copié !')
}
</script>

<template>
    <Head title="Codes d'accès" />

    <TenantLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Codes d'accès</h2>
                    <p class="text-sm text-gray-600 mt-1">Gérez les codes PIN et QR de vos clients</p>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('tenant.self-service.index')"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                    >
                        Retour
                    </Link>
                    <button
                        @click="showCreateModal = true"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouveau code
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                            <input
                                type="text"
                                v-model="filters.search"
                                @keyup.enter="applyFilters"
                                placeholder="Code, nom..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select
                                v-model="filters.status"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Tous</option>
                                <option value="active">Actif</option>
                                <option value="suspended">Suspendu</option>
                                <option value="expired">Expiré</option>
                                <option value="revoked">Révoqué</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                            <select
                                v-model="filters.site_id"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Tous les sites</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button
                                @click="applyFilters"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                            >
                                Filtrer
                            </button>
                            <button
                                @click="clearFilters"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                            >
                                Effacer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code PIN</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">QR Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validité</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="code in accessCodes.data" :key="code.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                                <span class="text-indigo-600 font-medium">
                                                    {{ code.customer?.first_name?.charAt(0) }}{{ code.customer?.last_name?.charAt(0) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ code.customer?.full_name }}</div>
                                                <div class="text-sm text-gray-500">{{ code.customer?.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <code class="px-3 py-1 bg-gray-100 rounded font-mono text-lg tracking-wider">
                                                {{ code.access_code }}
                                            </code>
                                            <button
                                                @click="copyToClipboard(code.access_code)"
                                                class="p-1 text-gray-400 hover:text-gray-600"
                                                title="Copier"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div v-if="code.is_master" class="mt-1">
                                            <span class="px-2 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">Principal</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="text-xs text-gray-500">{{ code.qr_code }}</code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ code.site?.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 text-xs rounded-full', statusColor(code.status)]">
                                            {{ statusLabel(code.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ code.use_count }}{{ code.max_uses ? ` / ${code.max_uses}` : '' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div v-if="code.is_permanent" class="text-green-600">Permanent</div>
                                        <div v-else>
                                            {{ formatDate(code.valid_from) }} - {{ formatDate(code.valid_until) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                v-if="code.status !== 'revoked'"
                                                @click="toggleCode(code)"
                                                :class="[
                                                    'px-3 py-1 text-xs rounded-lg transition',
                                                    code.status === 'active'
                                                        ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
                                                        : 'bg-green-100 text-green-700 hover:bg-green-200'
                                                ]"
                                            >
                                                {{ code.status === 'active' ? 'Suspendre' : 'Activer' }}
                                            </button>
                                            <button
                                                @click="regenerateCode(code)"
                                                class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition"
                                            >
                                                Régénérer
                                            </button>
                                            <button
                                                v-if="code.status !== 'revoked'"
                                                @click="revokeCode(code)"
                                                class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition"
                                            >
                                                Révoquer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="accessCodes.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                        <p>Aucun code d'accès trouvé</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="accessCodes.links && accessCodes.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                        <p class="text-sm text-gray-500">
                            Affichage de {{ accessCodes.from }} à {{ accessCodes.to }} sur {{ accessCodes.total }} résultats
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in accessCodes.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black opacity-50" @click="showCreateModal = false"></div>
                    <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Créer un code d'accès</h3>

                        <form @submit.prevent="createCode" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <input
                                    type="text"
                                    v-model="form.customer_id"
                                    placeholder="ID du client"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                                <select
                                    v-model="form.site_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                >
                                    <option value="">Sélectionner...</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id">
                                        {{ site.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="form.is_permanent" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Permanent</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="form.is_master" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">Code principal</span>
                                </label>
                            </div>

                            <div v-if="!form.is_permanent" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                                    <input
                                        type="date"
                                        v-model="form.valid_from"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                                    <input
                                        type="date"
                                        v-model="form.valid_until"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre max d'utilisations</label>
                                <input
                                    type="number"
                                    v-model="form.max_uses"
                                    min="1"
                                    placeholder="Illimité"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showCreateModal = false"
                                    class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                                >
                                    Créer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>
