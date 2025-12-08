<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    EyeIcon,
    DocumentDuplicateIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    template: Object,
})

const showPreview = ref(false)
const previewHtml = ref('')
const loadingPreview = ref(false)

const getCategoryColor = (cat) => {
    const colors = {
        system: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        tenant: 'bg-green-500/10 text-green-400 border-green-500/20',
        billing: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        support: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        marketing: 'bg-pink-500/10 text-pink-400 border-pink-500/20',
    }
    return colors[cat] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}

const loadPreview = async () => {
    loadingPreview.value = true
    try {
        const response = await fetch(route('superadmin.email-templates.preview', props.template.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        const data = await response.json()
        previewHtml.value = data.html
        showPreview.value = true
    } catch (error) {
        console.error('Preview error:', error)
    } finally {
        loadingPreview.value = false
    }
}

const duplicateTemplate = () => {
    router.post(route('superadmin.email-templates.duplicate', props.template.id))
}
</script>

<template>
    <Head :title="`Template - ${template.name}`" />

    <SuperAdminLayout :title="`Template - ${template.name}`">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('superadmin.email-templates.index')"
                        class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ template.name }}</h1>
                        <p class="mt-1 text-sm text-gray-400">{{ template.slug }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="loadPreview"
                        :disabled="loadingPreview"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        <EyeIcon class="h-5 w-5" />
                        Aperçu
                    </button>
                    <button
                        @click="duplicateTemplate"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        <DocumentDuplicateIcon class="h-5 w-5" />
                        Dupliquer
                    </button>
                    <Link
                        :href="route('superadmin.email-templates.edit', template.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                    >
                        <PencilSquareIcon class="h-5 w-5" />
                        Modifier
                    </Link>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                    <div>
                        <div class="text-sm text-gray-400">Catégorie</div>
                        <span :class="[getCategoryColor(template.category), 'mt-1 inline-flex px-2 py-1 text-xs rounded-full border']">
                            {{ template.category }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Statut</div>
                        <span :class="[
                            template.is_active ? 'bg-green-500/10 text-green-400' : 'bg-gray-500/10 text-gray-400',
                            'mt-1 inline-flex px-2 py-1 text-xs rounded-full'
                        ]">
                            {{ template.is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Créé le</div>
                        <div class="mt-1 text-white">{{ new Date(template.created_at).toLocaleDateString('fr-FR') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Modifié le</div>
                        <div class="mt-1 text-white">{{ new Date(template.updated_at).toLocaleDateString('fr-FR') }}</div>
                    </div>
                </div>
            </div>

            <!-- Subject -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Sujet</h2>
                <div class="p-4 bg-gray-900 rounded-lg text-gray-300 font-mono text-sm">
                    {{ template.subject }}
                </div>
            </div>

            <!-- HTML Content -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Contenu HTML</h2>
                <div class="p-4 bg-gray-900 rounded-lg overflow-auto max-h-96">
                    <pre class="text-gray-300 font-mono text-sm whitespace-pre-wrap">{{ template.body_html }}</pre>
                </div>
            </div>

            <!-- Text Content -->
            <div v-if="template.body_text" class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-3">Contenu Texte</h2>
                <div class="p-4 bg-gray-900 rounded-lg overflow-auto max-h-96">
                    <pre class="text-gray-300 font-mono text-sm whitespace-pre-wrap">{{ template.body_text }}</pre>
                </div>
            </div>

            <!-- Preview Modal -->
            <div v-if="showPreview" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/70" @click="showPreview = false"></div>
                    <div class="relative bg-gray-800 rounded-xl border border-gray-700 w-full max-w-4xl max-h-[80vh] overflow-hidden">
                        <div class="flex items-center justify-between p-4 border-b border-gray-700">
                            <h3 class="text-lg font-semibold text-white">Aperçu de l'email</h3>
                            <button @click="showPreview = false" class="text-gray-400 hover:text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-4 overflow-auto max-h-[calc(80vh-80px)]">
                            <div class="bg-white rounded-lg p-4" v-html="previewHtml"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
