<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    tenants: Array,
    plans: Array,
})

const form = useForm({
    name: '',
    key: '',
    description: '',
    is_enabled: false,
    enabled_for_tenants: [],
    enabled_for_plans: [],
})

const generateKey = () => {
    form.key = form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/(^_|_$)/g, '')
}

const submit = () => {
    form.post(route('superadmin.feature-flags.store'))
}
</script>

<template>
    <Head title="Nouveau Feature Flag" />

    <SuperAdminLayout title="Nouveau Feature Flag">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('superadmin.feature-flags.index')"
                    class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-white">Nouveau Feature Flag</h1>
                    <p class="mt-1 text-sm text-gray-400">Créez un nouveau flag pour contrôler une fonctionnalité</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Informations</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nom *</label>
                            <input
                                v-model="form.name"
                                @blur="generateKey"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="Dark Mode"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Clé unique *</label>
                            <input
                                v-model="form.key"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white font-mono placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="dark_mode"
                            />
                            <p class="mt-1 text-sm text-gray-500">Utilisé dans le code pour vérifier si la feature est active</p>
                            <p v-if="form.errors.key" class="mt-1 text-sm text-red-400">{{ form.errors.key }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                                placeholder="Active le mode sombre dans l'interface..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    v-model="form.is_enabled"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-gray-600 bg-gray-700 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm text-gray-300">Activer globalement</span>
                            </label>
                            <p class="mt-1 ml-8 text-sm text-gray-500">Si activé, la feature sera disponible pour tous (sauf restrictions ci-dessous)</p>
                        </div>
                    </div>
                </div>

                <!-- Plans -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Activer pour certains plans</h2>
                    <p class="text-sm text-gray-400 mb-4">Si des plans sont sélectionnés, seuls les tenants avec ces plans auront accès</p>

                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="plan in plans"
                            :key="plan"
                            class="flex items-center gap-2 px-4 py-2 bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors"
                        >
                            <input
                                type="checkbox"
                                :value="plan"
                                v-model="form.enabled_for_plans"
                                class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-purple-600 focus:ring-purple-500"
                            />
                            <span class="text-sm text-gray-300 capitalize">{{ plan }}</span>
                        </label>
                    </div>
                </div>

                <!-- Tenants -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Activer pour certains tenants</h2>
                    <p class="text-sm text-gray-400 mb-4">Sélectionnez des tenants spécifiques pour lesquels activer cette feature</p>

                    <div class="max-h-48 overflow-y-auto bg-gray-900 rounded-lg p-3 space-y-2">
                        <label
                            v-for="tenant in tenants"
                            :key="tenant.id"
                            class="flex items-center gap-3 cursor-pointer hover:bg-gray-800 p-2 rounded"
                        >
                            <input
                                type="checkbox"
                                :value="tenant.id"
                                v-model="form.enabled_for_tenants"
                                class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-purple-600 focus:ring-purple-500"
                            />
                            <span class="text-sm text-gray-300">{{ tenant.name }}</span>
                            <span class="text-xs text-gray-500 capitalize">({{ tenant.plan }})</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('superadmin.feature-flags.index')"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        Créer le flag
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
