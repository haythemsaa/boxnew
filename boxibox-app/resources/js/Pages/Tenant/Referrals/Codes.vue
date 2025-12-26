<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    LinkIcon,
    PlusIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    TrashIcon,
    PauseIcon,
    PlayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    codes: Object,
})

const showCreateModal = ref(false)
const copiedCode = ref(null)

const form = useForm({
    customer_id: null,
    name: '',
    code: '',
    max_uses: null,
    expires_at: '',
})

const createCode = () => {
    form.post(route('tenant.referrals.codes.create'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        },
    })
}

const toggleCode = (code) => {
    router.patch(route('tenant.referrals.codes.toggle', code.id))
}

const deleteCode = (code) => {
    if (confirm(`Supprimer le code "${code.code}" ?`)) {
        router.delete(route('tenant.referrals.codes.delete', code.id))
    }
}

const copyCode = (code) => {
    navigator.clipboard.writeText(code.code)
    copiedCode.value = code.id
    setTimeout(() => copiedCode.value = null, 2000)
}

const copyShareUrl = (code) => {
    const url = `${window.location.origin}/book?ref=${code.code}`
    navigator.clipboard.writeText(url)
    copiedCode.value = `url-${code.id}`
    setTimeout(() => copiedCode.value = null, 2000)
}
</script>

<template>
    <TenantLayout title="Codes de parrainage">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>

            <div class="max-w-6xl mx-auto relative z-10">
                <Link
                    :href="route('tenant.referrals.index')"
                    class="inline-flex items-center text-purple-100 hover:text-white mb-4 transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour au parrainage
                </Link>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <LinkIcon class="h-10 w-10 text-white" />
                        <div>
                            <h1 class="text-3xl font-bold text-white">Codes de parrainage</h1>
                            <p class="mt-1 text-purple-100">Gérez les codes de parrainage</p>
                        </div>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-white text-purple-700 rounded-xl hover:bg-purple-50 transition-colors font-medium"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Nouveau code
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div v-if="codes.data.length === 0" class="p-12 text-center">
                    <LinkIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun code</h3>
                    <p class="text-gray-500 mb-6">Créez des codes de parrainage pour vos clients</p>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Créer un code
                    </button>
                </div>

                <table v-else class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisations</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expire</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="code in codes.data" :key="code.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-bold text-purple-600">{{ code.code }}</span>
                                    <button
                                        @click="copyCode(code)"
                                        class="text-gray-400 hover:text-gray-600"
                                        :title="copiedCode === code.id ? 'Copié!' : 'Copier'"
                                    >
                                        <CheckIcon v-if="copiedCode === code.id" class="h-4 w-4 text-green-500" />
                                        <ClipboardDocumentIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="code.name" class="text-xs text-gray-500">{{ code.name }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="code.customer">{{ code.customer.full_name || code.customer.name }}</span>
                                <span v-else class="text-gray-400 italic">Code général</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-medium">{{ code.uses_count }}</span>
                                <span v-if="code.max_uses" class="text-gray-400"> / {{ code.max_uses }}</span>
                                <span v-else class="text-gray-400"> / illimité</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    :class="code.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ code.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ code.expires_at || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button
                                    @click="copyShareUrl(code)"
                                    class="inline-flex items-center px-2 py-1 text-purple-600 hover:text-purple-700"
                                    :title="copiedCode === `url-${code.id}` ? 'URL copiée!' : 'Copier URL de partage'"
                                >
                                    <LinkIcon class="h-4 w-4" />
                                </button>
                                <button
                                    @click="toggleCode(code)"
                                    class="inline-flex items-center px-2 py-1 text-gray-600 hover:text-gray-700"
                                    :title="code.is_active ? 'Désactiver' : 'Activer'"
                                >
                                    <PauseIcon v-if="code.is_active" class="h-4 w-4" />
                                    <PlayIcon v-else class="h-4 w-4" />
                                </button>
                                <button
                                    @click="deleteCode(code)"
                                    class="inline-flex items-center px-2 py-1 text-red-600 hover:text-red-700"
                                    title="Supprimer"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouveau code de parrainage</h3>

                <form @submit.prevent="createCode" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom (optionnel)</label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-purple-500"
                            placeholder="Ex: Code promo été"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code (optionnel, auto-généré si vide)</label>
                        <input
                            type="text"
                            v-model="form.code"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 font-mono uppercase focus:ring-2 focus:ring-purple-500"
                            placeholder="SUMMER2025"
                            maxlength="20"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Utilisations max (optionnel)</label>
                        <input
                            type="number"
                            v-model="form.max_uses"
                            min="1"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-purple-500"
                            placeholder="Illimité"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration (optionnel)</label>
                        <input
                            type="date"
                            v-model="form.expires_at"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-purple-500"
                        />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 disabled:opacity-50"
                        >
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
