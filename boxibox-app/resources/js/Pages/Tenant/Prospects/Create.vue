<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const form = useForm({
    type: 'individual',
    first_name: '',
    last_name: '',
    company_name: '',
    siret: '',
    email: '',
    phone: '',
    address: '',
    postal_code: '',
    city: '',
    source: 'website',
    box_size_interested: '',
    move_in_date: '',
    budget: '',
    notes: '',
})

const submit = () => {
    form.post(route('tenant.prospects.store'))
}
</script>

<template>
    <AuthenticatedLayout title="Nouveau Prospect">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Nouveau Prospect</h2>
                    <p class="mt-1 text-sm text-gray-500">Ajoutez un nouveau prospect à votre base</p>
                </div>
                <Link
                    :href="route('tenant.prospects.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    &larr; Retour à la liste
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Type Selection -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Type de prospect</h3>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                v-model="form.type"
                                value="individual"
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                            />
                            <span class="ml-2 text-sm text-gray-700">Particulier</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                v-model="form.type"
                                value="company"
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                            />
                            <span class="ml-2 text-sm text-gray-700">Entreprise</span>
                        </label>
                    </div>
                    <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Personal/Company Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        {{ form.type === 'company' ? 'Informations entreprise' : 'Informations personnelles' }}
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div v-if="form.type === 'company'" class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise *</label>
                            <input
                                type="text"
                                v-model="form.company_name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Nom de l'entreprise"
                            />
                            <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</p>
                        </div>

                        <div v-if="form.type === 'company'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">SIRET</label>
                            <input
                                type="text"
                                v-model="form.siret"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="123 456 789 00012"
                            />
                            <p v-if="form.errors.siret" class="mt-1 text-sm text-red-600">{{ form.errors.siret }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                            <input
                                type="text"
                                v-model="form.first_name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Jean"
                            />
                            <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                            <input
                                type="text"
                                v-model="form.last_name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Dupont"
                            />
                            <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Coordonnées</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input
                                type="email"
                                v-model="form.email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="jean.dupont@email.com"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <input
                                type="tel"
                                v-model="form.phone"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="+33 6 12 34 56 78"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <input
                                type="text"
                                v-model="form.address"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="123 rue de la Paix"
                            />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                            <input
                                type="text"
                                v-model="form.postal_code"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="75001"
                            />
                            <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">{{ form.errors.postal_code }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                            <input
                                type="text"
                                v-model="form.city"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Paris"
                            />
                            <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</p>
                        </div>
                    </div>
                </div>

                <!-- Prospect Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Détails du projet</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Source *</label>
                            <select
                                v-model="form.source"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            >
                                <option value="website">Site web</option>
                                <option value="phone">Téléphone</option>
                                <option value="email">Email</option>
                                <option value="referral">Recommandation</option>
                                <option value="walk_in">Visite</option>
                                <option value="social_media">Réseaux sociaux</option>
                                <option value="other">Autre</option>
                            </select>
                            <p v-if="form.errors.source" class="mt-1 text-sm text-red-600">{{ form.errors.source }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Taille de box souhaitée</label>
                            <select
                                v-model="form.box_size_interested"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            >
                                <option value="">Non défini</option>
                                <option value="1-5m²">1-5 m²</option>
                                <option value="5-10m²">5-10 m²</option>
                                <option value="10-20m²">10-20 m²</option>
                                <option value="20-50m²">20-50 m²</option>
                                <option value="+50m²">+50 m²</option>
                            </select>
                            <p v-if="form.errors.box_size_interested" class="mt-1 text-sm text-red-600">{{ form.errors.box_size_interested }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date d'entrée souhaitée</label>
                            <input
                                type="date"
                                v-model="form.move_in_date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.move_in_date" class="mt-1 text-sm text-red-600">{{ form.errors.move_in_date }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Budget mensuel</label>
                            <div class="relative">
                                <input
                                    type="number"
                                    v-model="form.budget"
                                    step="0.01"
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent pr-10"
                                    placeholder="100"
                                />
                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">EUR</span>
                            </div>
                            <p v-if="form.errors.budget" class="mt-1 text-sm text-red-600">{{ form.errors.budget }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Informations complémentaires sur le prospect..."
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end space-x-4">
                    <Link
                        :href="route('tenant.prospects.index')"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Enregistrement...' : 'Créer le prospect' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
