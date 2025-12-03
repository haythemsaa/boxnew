<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    CodeBracketIcon,
    PlusIcon,
    TrashIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    EyeIcon,
    PencilIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    widgets: Array,
})

const showCreateModal = ref(false)
const showEmbedModal = ref(false)
const selectedWidget = ref(null)
const copied = ref('')

const form = useForm({
    name: '',
    site_id: null,
    widget_type: 'full',
})

const createWidget = () => {
    form.post(route('tenant.bookings.widgets.create'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        },
    })
}

const deleteWidget = (widget) => {
    if (confirm(`Supprimer le widget "${widget.name}" ?`)) {
        router.delete(route('tenant.bookings.widgets.delete', widget.id))
    }
}

const showEmbed = (widget) => {
    selectedWidget.value = widget
    showEmbedModal.value = true
}

const copyCode = (code, type) => {
    navigator.clipboard.writeText(code)
    copied.value = type
    setTimeout(() => copied.value = '', 2000)
}

const widgetTypes = [
    { value: 'full', label: 'Complet', description: 'Interface complète de réservation' },
    { value: 'compact', label: 'Compact', description: 'Version condensée' },
    { value: 'button', label: 'Bouton', description: 'Bouton qui ouvre une popup' },
    { value: 'inline', label: 'Inline', description: 'Intégration directe dans la page' },
]
</script>

<template>
    <TenantLayout title="Widgets de réservation">
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
                        <CodeBracketIcon class="h-10 w-10 text-white" />
                        <div>
                            <h1 class="text-3xl font-bold text-white">Widgets</h1>
                            <p class="mt-1 text-teal-100">Intégrez la réservation sur votre site web</p>
                        </div>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-white text-teal-700 rounded-xl hover:bg-teal-50 transition-colors font-medium"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Nouveau widget
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <!-- Empty State -->
            <div v-if="widgets.length === 0" class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <CodeBracketIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun widget</h3>
                <p class="text-gray-500 mb-6">Créez un widget pour intégrer la réservation sur votre site</p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Créer un widget
                </button>
            </div>

            <!-- Widgets List -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="widget in widgets"
                    :key="widget.id"
                    class="bg-white rounded-2xl shadow-xl p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ widget.name }}</h3>
                            <p class="text-sm text-gray-500">{{ widget.site || 'Tous les sites' }}</p>
                        </div>
                        <span
                            :class="widget.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                            class="px-3 py-1 rounded-full text-xs font-medium"
                        >
                            {{ widget.is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-gray-500">Type:</span>
                            <span class="ml-2 font-medium">{{ widget.widget_type }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Vues:</span>
                            <span class="ml-2 font-medium">{{ widget.views_count }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Réservations:</span>
                            <span class="ml-2 font-medium">{{ widget.bookings_count }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Créé:</span>
                            <span class="ml-2 font-medium">{{ widget.created_at }}</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 pt-4 border-t border-gray-100">
                        <button
                            @click="showEmbed(widget)"
                            class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-teal-50 text-teal-700 rounded-lg hover:bg-teal-100 transition-colors text-sm"
                        >
                            <CodeBracketIcon class="h-4 w-4 mr-1" />
                            Code d'intégration
                        </button>
                        <button
                            @click="deleteWidget(widget)"
                            class="px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors"
                        >
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouveau widget</h3>
                <form @submit.prevent="createWidget" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom du widget</label>
                        <input
                            type="text"
                            v-model="form.name"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            placeholder="Widget principal"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type de widget</label>
                        <select
                            v-model="form.widget_type"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                        >
                            <option v-for="type in widgetTypes" :key="type.value" :value="type.value">
                                {{ type.label }} - {{ type.description }}
                            </option>
                        </select>
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

        <!-- Embed Modal -->
        <div v-if="showEmbedModal && selectedWidget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Code d'intégration - {{ selectedWidget.name }}</h3>

                <div class="space-y-6">
                    <!-- Script Embed -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Code JavaScript (recommandé)</label>
                            <button
                                @click="copyCode(selectedWidget.embed_code, 'js')"
                                class="text-sm text-teal-600 hover:text-teal-700 flex items-center"
                            >
                                <ClipboardDocumentIcon v-if="copied !== 'js'" class="h-4 w-4 mr-1" />
                                <CheckIcon v-else class="h-4 w-4 mr-1" />
                                {{ copied === 'js' ? 'Copié!' : 'Copier' }}
                            </button>
                        </div>
                        <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 text-sm overflow-x-auto"><code>{{ selectedWidget.embed_code }}</code></pre>
                    </div>

                    <!-- iFrame Embed -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Code iFrame</label>
                            <button
                                @click="copyCode(selectedWidget.iframe_code, 'iframe')"
                                class="text-sm text-teal-600 hover:text-teal-700 flex items-center"
                            >
                                <ClipboardDocumentIcon v-if="copied !== 'iframe'" class="h-4 w-4 mr-1" />
                                <CheckIcon v-else class="h-4 w-4 mr-1" />
                                {{ copied === 'iframe' ? 'Copié!' : 'Copier' }}
                            </button>
                        </div>
                        <pre class="bg-gray-900 text-gray-100 rounded-xl p-4 text-sm overflow-x-auto"><code>{{ selectedWidget.iframe_code }}</code></pre>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button
                        @click="showEmbedModal = false"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
