<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    tenants: Array,
})

const form = useForm({
    title: '',
    content: '',
    type: 'info',
    target: 'all',
    target_tenant_ids: [],
    is_active: true,
    is_dismissible: true,
    starts_at: '',
    ends_at: '',
})

const submit = () => {
    form.post(route('superadmin.announcements.store'))
}
</script>

<template>
    <Head title="Nouvelle Annonce" />

    <SuperAdminLayout title="Nouvelle Annonce">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('superadmin.announcements.index')"
                    class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-white">Nouvelle Annonce</h1>
                    <p class="mt-1 text-sm text-gray-400">Créez une annonce pour vos utilisateurs</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Content -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Contenu</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Titre *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="Maintenance prévue le..."
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-400">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Message *</label>
                            <textarea
                                v-model="form.content"
                                rows="4"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="Détails de l'annonce..."
                            ></textarea>
                            <p v-if="form.errors.content" class="mt-1 text-sm text-red-400">{{ form.errors.content }}</p>
                        </div>
                    </div>
                </div>

                <!-- Type & Target -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Type & Cible</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Type *</label>
                            <select
                                v-model="form.type"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="info">Information</option>
                                <option value="warning">Avertissement</option>
                                <option value="success">Succès</option>
                                <option value="error">Erreur</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Cible *</label>
                            <select
                                v-model="form.target"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="all">Tous les utilisateurs</option>
                                <option value="tenants">Tenants uniquement</option>
                                <option value="specific">Tenants spécifiques</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="form.target === 'specific'" class="mt-4">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Sélectionner les tenants</label>
                        <div class="max-h-48 overflow-y-auto bg-gray-900 rounded-lg p-3 space-y-2">
                            <label
                                v-for="tenant in tenants"
                                :key="tenant.id"
                                class="flex items-center gap-3 cursor-pointer hover:bg-gray-800 p-2 rounded"
                            >
                                <input
                                    type="checkbox"
                                    :value="tenant.id"
                                    v-model="form.target_tenant_ids"
                                    class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm text-gray-300">{{ tenant.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Paramètres</h2>

                    <div class="space-y-4">
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-gray-600 bg-gray-700 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm text-gray-300">Activer immédiatement</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    v-model="form.is_dismissible"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-gray-600 bg-gray-700 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm text-gray-300">Peut être fermée</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Date de début (optionnel)</label>
                                <input
                                    v-model="form.starts_at"
                                    type="datetime-local"
                                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Date de fin (optionnel)</label>
                                <input
                                    v-model="form.ends_at"
                                    type="datetime-local"
                                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('superadmin.announcements.index')"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        Créer l'annonce
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
