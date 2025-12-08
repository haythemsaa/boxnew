<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    tenant: Object,
})

const form = useForm({
    name: props.tenant.name,
    slug: props.tenant.slug,
    email: props.tenant.email,
    phone: props.tenant.phone || '',
    address: props.tenant.address || '',
    city: props.tenant.city || '',
    postal_code: props.tenant.postal_code || '',
    country: props.tenant.country || 'FR',
    subscription_plan: props.tenant.subscription_plan,
    status: props.tenant.status,
    trial_ends_at: props.tenant.trial_ends_at?.split('T')[0] || '',
})

const submit = () => {
    form.put(route('superadmin.tenants.update', props.tenant.id))
}
</script>

<template>
    <Head :title="`Modifier: ${tenant.name}`" />

    <SuperAdminLayout :title="`Modifier: ${tenant.name}`">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('superadmin.tenants.show', tenant.id)"
                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-white">Modifier le Tenant</h1>
                    <p class="mt-1 text-sm text-gray-400">{{ tenant.name }}</p>
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
                                <option value="cancelled">Annulé</option>
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

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('superadmin.tenants.show', tenant.id)"
                        class="px-4 py-2 text-gray-300 hover:text-white transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
                    >
                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
