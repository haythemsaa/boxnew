<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    widgets: Array,
    sites: Array,
})

const showCreateModal = ref(false)
const showCodeModal = ref(false)
const selectedWidget = ref(null)

const form = useForm({
    name: '',
    site_id: '',
    show_prices: true,
    show_availability: true,
    require_contact: false,
    enable_booking: true,
    redirect_url: '',
    custom_css: '',
})

const createWidget = () => {
    form.post(route('tenant.calculator.widgets.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        }
    })
}

const deleteWidget = (widget) => {
    if (confirm('Supprimer ce widget ?')) {
        router.delete(route('tenant.calculator.widgets.destroy', widget.id))
    }
}

const showEmbedCode = (widget) => {
    selectedWidget.value = widget
    showCodeModal.value = true
}

const getEmbedCode = (widget) => {
    const baseUrl = window.location.origin
    return `<iframe src="${baseUrl}/calculator/widget/${widget.embed_code}" width="100%" height="600" frameborder="0"></iframe>`
}

const copyCode = () => {
    if (selectedWidget.value) {
        navigator.clipboard.writeText(getEmbedCode(selectedWidget.value))
    }
}
</script>

<template>
    <Head title="Widgets Calculateur" />

    <TenantLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Widgets Calculateur</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Intégrez le calculateur de taille sur votre site web</p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                >
                    Nouveau Widget
                </button>
            </div>

            <!-- Widgets List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="widget in widgets"
                    :key="widget.id"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow p-6"
                >
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ widget.name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Site: {{ widget.site?.name || 'Tous les sites' }}
                    </p>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span v-if="widget.show_prices" class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Prix</span>
                        <span v-if="widget.show_availability" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">Disponibilité</span>
                        <span v-if="widget.enable_booking" class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">Réservation</span>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="showEmbedCode(widget)"
                            class="flex-1 px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                        >
                            Code d'intégration
                        </button>
                        <button
                            @click="deleteWidget(widget)"
                            class="px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div
                    v-if="!widgets?.length"
                    class="col-span-full text-center py-12 bg-white dark:bg-gray-800 rounded-lg"
                >
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Aucun widget créé</p>
                    <button
                        @click="showCreateModal = true"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                        Créer votre premier widget
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Nouveau Widget</h2>
                <form @submit.prevent="createWidget">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du widget</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site (optionnel)</label>
                            <select
                                v-model="form.site_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            >
                                <option value="">Tous les sites</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.show_prices" class="rounded text-indigo-600" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Afficher les prix</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.show_availability" class="rounded text-indigo-600" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Afficher disponibilité</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.enable_booking" class="rounded text-indigo-600" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Activer réservation</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.require_contact" class="rounded text-indigo-600" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Exiger contact</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Embed Code Modal -->
        <div v-if="showCodeModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Code d'intégration</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Copiez ce code et collez-le dans votre site web
                </p>
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg font-mono text-sm break-all">
                    {{ selectedWidget ? getEmbedCode(selectedWidget) : '' }}
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="showCodeModal = false"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        Fermer
                    </button>
                    <button
                        @click="copyCode"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                        Copier
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
