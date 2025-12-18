<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    CubeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    module: Object,
    categories: Object,
})

const form = useForm({
    name: props.module.name || '',
    code: props.module.code || '',
    description: props.module.description || '',
    category: props.module.category || 'core',
    is_core: props.module.is_core || false,
    is_active: props.module.is_active || true,
    monthly_price: props.module.monthly_price || 0,
    yearly_price: props.module.yearly_price || 0,
    features: props.module.features || [],
    sort_order: props.module.sort_order || 0,
})

const submit = () => {
    form.put(route('superadmin.modules.update', props.module.id))
}
</script>

<template>
    <Head :title="`Modifier Module: ${module.name}`" />

    <SuperAdminLayout title="Modifier Module">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    :href="route('superadmin.modules.index')"
                    class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Retour aux modules
                </Link>
                <h1 class="mt-2 text-2xl font-bold text-white">Modifier: {{ module.name }}</h1>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 space-y-6">
                    <!-- Informations de base -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Nom du module *
                            </label>
                            <input
                                type="text"
                                v-model="form.name"
                                class="w-full bg-gray-700 border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-purple-500"
                                placeholder="Ex: Gestion des clients"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Code unique *
                            </label>
                            <input
                                type="text"
                                v-model="form.code"
                                class="w-full bg-gray-700 border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-purple-500 font-mono"
                                placeholder="Ex: crm"
                                disabled
                            />
                            <p class="mt-1 text-xs text-gray-500">Le code ne peut pas etre modifie</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full bg-gray-700 border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-purple-500"
                            placeholder="Description du module..."
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Categorie
                            </label>
                            <select
                                v-model="form.category"
                                class="w-full bg-gray-700 border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-purple-500"
                            >
                                <option v-for="(cat, key) in categories" :key="key" :value="key">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Ordre d'affichage
                            </label>
                            <input
                                type="number"
                                v-model="form.sort_order"
                                class="w-full bg-gray-700 border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-purple-500"
                                min="0"
                            />
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_core"
                                class="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                            />
                            <span class="text-sm text-gray-300">Module Core (gratuit et inclus)</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500"
                            />
                            <span class="text-sm text-gray-300">Module actif</span>
                        </label>
                    </div>
                </div>

                <!-- Tarification -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Tarification</h3>

                    <div v-if="form.is_core" class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 text-blue-400 text-sm">
                        Les modules Core sont gratuits et inclus dans tous les abonnements.
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Prix mensuel (EUR)
                            </label>
                            <input
                                type="number"
                                v-model="form.monthly_price"
                                step="0.01"
                                min="0"
                                class="w-full bg-gray-700 border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-purple-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Prix annuel (EUR)
                            </label>
                            <input
                                type="number"
                                v-model="form.yearly_price"
                                step="0.01"
                                min="0"
                                class="w-full bg-gray-700 border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-purple-500"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Suggestion: {{ (form.monthly_price * 10).toFixed(2) }} EUR (2 mois offerts)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('superadmin.modules.index')"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
