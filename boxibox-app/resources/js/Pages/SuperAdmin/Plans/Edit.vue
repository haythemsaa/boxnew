<template>
    <SuperAdminLayout title="Modifier le Plan">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link :href="route('superadmin.plans.index')" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        <i class="fas fa-arrow-left mr-1"></i> Retour aux plans
                    </Link>
                    <h2 class="text-3xl font-bold text-gray-900">Modifier: {{ plan.name }}</h2>
                    <p class="mt-1 text-sm text-gray-600">Code: {{ plan.code }}</p>
                </div>

                <form @submit.prevent="submitForm" class="space-y-8">
                    <!-- Informations de base -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                            Informations de base
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                                <input type="text" :value="plan.code" disabled
                                    class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500">
                                <p class="text-xs text-gray-500 mt-1">Le code ne peut pas etre modifie</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                                <input type="text" v-model="form.name" required
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                                <textarea v-model="form.description" required rows="2"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Couleur Badge</label>
                                <select v-model="form.badge_color" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="blue">Bleu</option>
                                    <option value="green">Vert</option>
                                    <option value="purple">Violet</option>
                                    <option value="orange">Orange</option>
                                    <option value="red">Rouge</option>
                                    <option value="gray">Gris</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                                <input type="number" v-model="form.sort_order" min="0"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Tarification -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-euro-sign mr-2 text-green-500"></i>
                            Tarification
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prix mensuel (EUR) *</label>
                                <input type="number" v-model="form.monthly_price" required min="0" step="0.01"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Prix annuel (EUR) *</label>
                                <input type="number" v-model="form.yearly_price" required min="0" step="0.01"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remise annuelle (%)</label>
                                <input type="number" v-model="form.yearly_discount" min="0" max="100"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Limites -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-sliders-h mr-2 text-orange-500"></i>
                            Limites
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">Laissez vide pour illimite</p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Sites</label>
                                <input type="number" v-model="form.max_sites" min="1"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Boxes</label>
                                <input type="number" v-model="form.max_boxes" min="1"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Utilisateurs</label>
                                <input type="number" v-model="form.max_users" min="1"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Clients</label>
                                <input type="number" v-model="form.max_customers" min="1"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Quotas Email/SMS -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-envelope mr-2 text-purple-500"></i>
                            Quotas Email & SMS
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Definissez les quotas mensuels inclus dans le plan. Les tenants peuvent acheter des credits supplementaires.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-envelope text-blue-500 mr-1"></i>
                                    Emails par mois
                                </label>
                                <input type="number" v-model="form.emails_per_month" min="0"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">0 = pas d'emails inclus</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-sms text-green-500 mr-1"></i>
                                    SMS par mois
                                </label>
                                <input type="number" v-model="form.sms_per_month" min="0"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">0 = pas de SMS inclus</p>
                            </div>
                        </div>

                        <div class="border-t pt-4 space-y-4">
                            <h4 class="font-medium text-gray-900">Options avancees</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                    <input type="checkbox" v-model="form.email_tracking_enabled"
                                        class="h-5 w-5 text-blue-600 rounded">
                                    <div>
                                        <span class="font-medium text-gray-900">Tracking emails</span>
                                        <p class="text-xs text-gray-500">Suivi des ouvertures et clics</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                    <input type="checkbox" v-model="form.custom_email_provider_allowed"
                                        class="h-5 w-5 text-blue-600 rounded">
                                    <div>
                                        <span class="font-medium text-gray-900">Fournisseur email custom</span>
                                        <p class="text-xs text-gray-500">Le tenant peut utiliser son propre API</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                    <input type="checkbox" v-model="form.custom_sms_provider_allowed"
                                        class="h-5 w-5 text-green-600 rounded">
                                    <div>
                                        <span class="font-medium text-gray-900">Fournisseur SMS custom</span>
                                        <p class="text-xs text-gray-500">Le tenant peut utiliser son propre API</p>
                                    </div>
                                </label>

                                <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                    <input type="checkbox" v-model="form.api_access"
                                        class="h-5 w-5 text-purple-600 rounded">
                                    <div>
                                        <span class="font-medium text-gray-900">Acces API</span>
                                        <p class="text-xs text-gray-500">Acces a l'API BoxiBox</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-headset mr-2 text-blue-500"></i>
                            Support
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                <input type="checkbox" v-model="form.includes_support"
                                    class="h-5 w-5 text-blue-600 rounded">
                                <span class="font-medium text-gray-900">Support inclus</span>
                            </label>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Niveau de support</label>
                                <select v-model="form.support_level" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="none">Aucun</option>
                                    <option value="email">Email</option>
                                    <option value="priority">Prioritaire</option>
                                    <option value="dedicated">Dedie</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Modules inclus -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-puzzle-piece mr-2 text-indigo-500"></i>
                            Modules inclus
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">Selectionnez les modules inclus dans ce plan</p>

                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <label v-for="module in modules" :key="module.id"
                                class="flex items-center space-x-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                                :class="form.included_modules.includes(module.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                <input type="checkbox" :value="module.id" v-model="form.included_modules"
                                    class="h-4 w-4 text-blue-600 rounded">
                                <div>
                                    <i :class="module.icon" class="text-gray-500 mr-1"></i>
                                    <span class="text-sm font-medium">{{ module.name }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-cog mr-2 text-gray-500"></i>
                            Options
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                <input type="checkbox" v-model="form.is_popular"
                                    class="h-5 w-5 text-purple-600 rounded">
                                <div>
                                    <span class="font-medium text-gray-900">Plan populaire</span>
                                    <p class="text-xs text-gray-500">Badge "Populaire"</p>
                                </div>
                            </label>

                            <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                <input type="checkbox" v-model="form.is_active"
                                    class="h-5 w-5 text-green-600 rounded">
                                <div>
                                    <span class="font-medium text-gray-900">Actif</span>
                                    <p class="text-xs text-gray-500">Visible aux clients</p>
                                </div>
                            </label>

                            <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer">
                                <input type="checkbox" v-model="form.whitelabel"
                                    class="h-5 w-5 text-indigo-600 rounded">
                                <div>
                                    <span class="font-medium text-gray-900">White label</span>
                                    <p class="text-xs text-gray-500">Marque personnalisee</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-4">
                        <Link :href="route('superadmin.plans.index')"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            <i class="fas fa-save mr-2"></i>
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    plan: Object,
    modules: Array,
});

const form = useForm({
    name: props.plan.name,
    description: props.plan.description,
    badge_color: props.plan.badge_color || 'blue',
    monthly_price: props.plan.monthly_price,
    yearly_price: props.plan.yearly_price,
    yearly_discount: props.plan.yearly_discount,
    max_sites: props.plan.max_sites,
    max_boxes: props.plan.max_boxes,
    max_users: props.plan.max_users,
    max_customers: props.plan.max_customers,
    includes_support: props.plan.includes_support,
    support_level: props.plan.support_level || 'email',
    included_modules: props.plan.included_modules || [],
    features: props.plan.features || [],
    is_popular: props.plan.is_popular,
    is_active: props.plan.is_active,
    sort_order: props.plan.sort_order,
    // Quotas Email/SMS
    emails_per_month: props.plan.emails_per_month ?? 500,
    sms_per_month: props.plan.sms_per_month ?? 0,
    email_tracking_enabled: props.plan.email_tracking_enabled ?? true,
    custom_email_provider_allowed: props.plan.custom_email_provider_allowed ?? false,
    custom_sms_provider_allowed: props.plan.custom_sms_provider_allowed ?? false,
    api_access: props.plan.api_access ?? false,
    whitelabel: props.plan.whitelabel ?? false,
});

const submitForm = () => {
    form.put(route('superadmin.plans.update', props.plan.id));
};
</script>
