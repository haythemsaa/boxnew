<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    template: Object,
    availableVariables: Object,
})

const form = useForm({
    name: props.template.name,
    slug: props.template.slug,
    subject: props.template.subject,
    body_html: props.template.body_html,
    body_text: props.template.body_text || '',
    category: props.template.category,
    variables: props.template.variables || [],
    is_active: props.template.is_active,
})

const insertVariable = (variable) => {
    form.body_html += variable
}

const submit = () => {
    form.put(route('superadmin.email-templates.update', props.template.id))
}
</script>

<template>
    <Head :title="`Modifier - ${template.name}`" />

    <SuperAdminLayout :title="`Modifier - ${template.name}`">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('superadmin.email-templates.index')"
                    class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-white">Modifier le Template</h1>
                    <p class="mt-1 text-sm text-gray-400">{{ template.name }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Informations de base</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nom *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Slug *</label>
                            <input
                                v-model="form.slug"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-400">{{ form.errors.slug }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Catégorie *</label>
                            <select
                                v-model="form.category"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="system">Système</option>
                                <option value="tenant">Tenant</option>
                                <option value="billing">Facturation</option>
                                <option value="support">Support</option>
                                <option value="marketing">Marketing</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-400">{{ form.errors.category }}</p>
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-gray-600 bg-gray-700 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm text-gray-300">Template actif</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Sujet *</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                        />
                        <p v-if="form.errors.subject" class="mt-1 text-sm text-red-400">{{ form.errors.subject }}</p>
                    </div>
                </div>

                <!-- Variables -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Variables disponibles</h2>
                    <p class="text-sm text-gray-400 mb-4">Cliquez sur une variable pour l'insérer dans le contenu HTML</p>

                    <div class="space-y-4">
                        <div v-for="(vars, group) in availableVariables" :key="group">
                            <h3 class="text-sm font-medium text-gray-300 mb-2">{{ group }}</h3>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="(description, variable) in vars"
                                    :key="variable"
                                    @click.prevent="insertVariable(variable)"
                                    type="button"
                                    class="px-3 py-1 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                                    :title="description"
                                >
                                    {{ variable }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Contenu</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Contenu HTML *</label>
                            <textarea
                                v-model="form.body_html"
                                rows="12"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500 font-mono text-sm"
                            ></textarea>
                            <p v-if="form.errors.body_html" class="mt-1 text-sm text-red-400">{{ form.errors.body_html }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Contenu texte (optionnel)</label>
                            <textarea
                                v-model="form.body_text"
                                rows="6"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500 font-mono text-sm"
                            ></textarea>
                            <p class="mt-1 text-sm text-gray-500">Version texte brut pour les clients email qui ne supportent pas HTML</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('superadmin.email-templates.index')"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
