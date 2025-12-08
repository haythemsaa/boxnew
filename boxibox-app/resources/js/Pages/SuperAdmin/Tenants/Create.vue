<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const form = useForm({
    name: '',
    slug: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    postal_code: '',
    country: 'FR',
    subscription_plan: 'basic',
    status: 'trial',
    trial_ends_at: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
})

const submit = () => {
    form.post(route('superadmin.tenants.store'))
}

const generateSlug = () => {
    form.slug = form.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '')
}
</script>

<template>
    <Head title="Créer un Tenant" />

    <SuperAdminLayout title="Créer un Tenant">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('superadmin.tenants.index')"
                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-white">Créer un nouveau Tenant</h1>
                    <p class="mt-1 text-sm text-gray-400">Remplissez les informations pour créer un nouveau tenant</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tenant Info -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h2 class="text-lg font-medium text-white mb-4">Informations du Tenant</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nom *</label>
                            <input
                                v-model="form.name"
                                @blur="generateSlug"
                                type="text"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Slug</label>
                            <input
                                v-model="form.slug"
                                type="text"
                                placeholder="auto-généré"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-400">{{ form.errors.slug }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Téléphone</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-300 mb-1">Adresse</label>
                            <input
                                v-model="form.address"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Ville</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Code Postal</label>
                            <input
                                v-model="form.postal_code"
                                type="text"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Pays</label>
                            <select
                                v-model="form.country"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                                <option value="LU">Luxembourg</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Subscription -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h2 class="text-lg font-medium text-white mb-4">Abonnement</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Plan *</label>
                            <select
                                v-model="form.subscription_plan"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="free">Free (0€/mois)</option>
                                <option value="starter">Starter (49€/mois)</option>
                                <option value="professional">Professional (99€/mois)</option>
                                <option value="enterprise">Enterprise (249€/mois)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Statut *</label>
                            <select
                                v-model="form.status"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="trial">En essai</option>
                                <option value="active">Actif</option>
                                <option value="suspended">Suspendu</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Fin de période d'essai</label>
                            <input
                                v-model="form.trial_ends_at"
                                type="date"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Admin User -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h2 class="text-lg font-medium text-white mb-4">Compte Administrateur</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nom *</label>
                            <input
                                v-model="form.admin_name"
                                type="text"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.admin_name" class="mt-1 text-sm text-red-400">{{ form.errors.admin_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                            <input
                                v-model="form.admin_email"
                                type="email"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.admin_email" class="mt-1 text-sm text-red-400">{{ form.errors.admin_email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Mot de passe *</label>
                            <input
                                v-model="form.admin_password"
                                type="password"
                                required
                                minlength="8"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.admin_password" class="mt-1 text-sm text-red-400">{{ form.errors.admin_password }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('superadmin.tenants.index')"
                        class="px-4 py-2 text-gray-300 hover:text-white transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
                    >
                        {{ form.processing ? 'Création...' : 'Créer le Tenant' }}
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
