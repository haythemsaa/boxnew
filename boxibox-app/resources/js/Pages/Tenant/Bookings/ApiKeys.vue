<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    KeyIcon,
    PlusIcon,
    TrashIcon,
    ArrowPathIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    apiKeys: Array,
})

const showCreateModal = ref(false)
const showKeyModal = ref(false)
const newSecret = ref('')
const copied = ref('')
const visibleKey = ref(null)

const form = useForm({
    name: '',
    permissions: [],
    ip_whitelist: [],
})

const availablePermissions = [
    { value: 'bookings.read', label: 'Lire les réservations' },
    { value: 'bookings.create', label: 'Créer des réservations' },
    { value: 'bookings.update', label: 'Modifier les réservations' },
    { value: 'sites.read', label: 'Lire les sites' },
    { value: 'boxes.read', label: 'Lire les boxes' },
    { value: '*', label: 'Accès complet' },
]

const createApiKey = () => {
    form.post(route('tenant.bookings.api-keys.create'), {
        onSuccess: (page) => {
            showCreateModal.value = false
            form.reset()
            if (page.props.flash?.new_api_secret) {
                newSecret.value = page.props.flash.new_api_secret
                showKeyModal.value = true
            }
        },
    })
}

const deleteApiKey = (key) => {
    if (confirm(`Supprimer la clé "${key.name}" ? Cette action est irréversible.`)) {
        router.delete(route('tenant.bookings.api-keys.delete', key.id))
    }
}

const regenerateKey = (key) => {
    if (confirm('Régénérer le secret de cette clé ? L\'ancien secret ne sera plus valide.')) {
        router.post(route('tenant.bookings.api-keys.regenerate', key.id), {}, {
            onSuccess: (page) => {
                if (page.props.flash?.new_api_secret) {
                    newSecret.value = page.props.flash.new_api_secret
                    showKeyModal.value = true
                }
            },
        })
    }
}

const toggleActive = (key) => {
    router.patch(route('tenant.bookings.api-keys.update', key.id), {
        is_active: !key.is_active,
    })
}

const copyValue = (value, type) => {
    navigator.clipboard.writeText(value)
    copied.value = type
    setTimeout(() => copied.value = '', 2000)
}

const togglePermission = (permission) => {
    const index = form.permissions.indexOf(permission)
    if (index > -1) {
        form.permissions.splice(index, 1)
    } else {
        form.permissions.push(permission)
    }
}
</script>

<template>
    <TenantLayout title="Clés API">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-cyan-600 to-teal-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>

            <div class="max-w-6xl mx-auto relative z-10">
                <Link
                    :href="route('tenant.bookings.index')"
                    class="inline-flex items-center text-teal-100 hover:text-white mb-4 transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour aux réservations
                </Link>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <KeyIcon class="h-10 w-10 text-white" />
                        <div>
                            <h1 class="text-3xl font-bold text-white">Clés API</h1>
                            <p class="mt-1 text-teal-100">Gérez les accès API pour les intégrations</p>
                        </div>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-white text-teal-700 rounded-xl hover:bg-teal-50 transition-colors font-medium"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Nouvelle clé
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <!-- Empty State -->
            <div v-if="apiKeys.length === 0" class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <KeyIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune clé API</h3>
                <p class="text-gray-500 mb-6">Créez des clés API pour intégrer les réservations avec d'autres systèmes</p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Créer une clé
                </button>
            </div>

            <!-- API Keys List -->
            <div v-else class="space-y-4">
                <div
                    v-for="key in apiKeys"
                    :key="key.id"
                    class="bg-white rounded-2xl shadow-xl p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ key.name }}</h3>
                            <p class="text-sm text-gray-500">Créée le {{ key.created_at }}</p>
                        </div>
                        <button
                            @click="toggleActive(key)"
                            :class="key.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1 rounded-full text-xs font-medium"
                        >
                            {{ key.is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-500">Clé API</span>
                            <button
                                @click="copyValue(key.api_key, 'key-' + key.id)"
                                class="text-sm text-teal-600 hover:text-teal-700 flex items-center"
                            >
                                <ClipboardDocumentIcon v-if="copied !== 'key-' + key.id" class="h-4 w-4 mr-1" />
                                <CheckIcon v-else class="h-4 w-4 mr-1" />
                                {{ copied === 'key-' + key.id ? 'Copié!' : 'Copier' }}
                            </button>
                        </div>
                        <code class="font-mono text-sm text-gray-900">{{ key.api_key }}</code>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                        <div>
                            <span class="text-gray-500">Requêtes:</span>
                            <span class="ml-2 font-medium">{{ key.requests_count }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Dernière utilisation:</span>
                            <span class="ml-2 font-medium">{{ key.last_used_at || 'Jamais' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-500">Permissions:</span>
                            <span class="ml-2">
                                <span
                                    v-for="perm in (key.permissions || ['*'])"
                                    :key="perm"
                                    class="inline-block px-2 py-0.5 bg-teal-100 text-teal-800 rounded text-xs mr-1"
                                >
                                    {{ perm }}
                                </span>
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 pt-4 border-t border-gray-100">
                        <button
                            @click="regenerateKey(key)"
                            class="inline-flex items-center px-3 py-2 bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition-colors text-sm"
                        >
                            <ArrowPathIcon class="h-4 w-4 mr-1" />
                            Régénérer le secret
                        </button>
                        <button
                            @click="deleteApiKey(key)"
                            class="px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors"
                        >
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Documentation -->
            <div class="bg-white rounded-2xl shadow-xl p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Documentation API</h3>
                <div class="prose prose-sm max-w-none text-gray-600">
                    <p>Utilisez les headers suivants pour authentifier vos requêtes:</p>
                    <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 text-sm overflow-x-auto"><code>X-API-Key: votre_cle_api
X-API-Secret: votre_secret</code></pre>
                    <p class="mt-4">Endpoints disponibles:</p>
                    <ul class="space-y-2">
                        <li><code class="bg-gray-100 px-2 py-0.5 rounded">GET /api/v1/booking/sites</code> - Liste des sites</li>
                        <li><code class="bg-gray-100 px-2 py-0.5 rounded">GET /api/v1/booking/sites/{id}/boxes</code> - Boxes disponibles</li>
                        <li><code class="bg-gray-100 px-2 py-0.5 rounded">POST /api/v1/booking/bookings</code> - Créer une réservation</li>
                        <li><code class="bg-gray-100 px-2 py-0.5 rounded">GET /api/v1/booking/bookings</code> - Liste des réservations</li>
                        <li><code class="bg-gray-100 px-2 py-0.5 rounded">PATCH /api/v1/booking/bookings/{uuid}/status</code> - Modifier le statut</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouvelle clé API</h3>
                <form @submit.prevent="createApiKey" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la clé</label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                            placeholder="Intégration CRM"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                        <div class="space-y-2">
                            <label
                                v-for="perm in availablePermissions"
                                :key="perm.value"
                                class="flex items-center"
                            >
                                <input
                                    type="checkbox"
                                    :checked="form.permissions.includes(perm.value)"
                                    @change="togglePermission(perm.value)"
                                    class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                                />
                                <span class="ml-2 text-sm text-gray-700">{{ perm.label }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors disabled:opacity-50"
                        >
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- New Secret Modal -->
        <div v-if="showKeyModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg mx-4">
                <div class="text-center mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <KeyIcon class="h-6 w-6 text-yellow-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Votre secret API</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Copiez ce secret maintenant. Il ne sera plus affiché.
                    </p>
                </div>

                <div class="bg-gray-100 rounded-xl p-4 mb-4">
                    <div class="flex items-center justify-between">
                        <code class="font-mono text-sm text-gray-900 break-all">{{ newSecret }}</code>
                        <button
                            @click="copyValue(newSecret, 'new-secret')"
                            class="ml-2 text-teal-600 hover:text-teal-700"
                        >
                            <ClipboardDocumentIcon v-if="copied !== 'new-secret'" class="h-5 w-5" />
                            <CheckIcon v-else class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <div class="flex justify-center">
                    <button
                        @click="showKeyModal = false; newSecret = ''"
                        class="px-6 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors"
                    >
                        J'ai copié le secret
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
