<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mon Profil</h1>
                <p class="text-gray-600 dark:text-gray-400">Gerez vos informations personnelles</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Profile Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Personal Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-user mr-2 text-blue-600"></i>
                                Informations personnelles
                            </h2>
                        </div>
                        <form @submit.prevent="updateProfile" class="p-6 space-y-4">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prenom</label>
                                    <input
                                        v-model="profileForm.first_name"
                                        type="text"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                    <input
                                        v-model="profileForm.last_name"
                                        type="text"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input
                                    :value="customer.email"
                                    type="email"
                                    disabled
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-500"
                                />
                                <p class="text-xs text-gray-500 mt-1">Contactez-nous pour modifier votre email</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telephone</label>
                                <input
                                    v-model="profileForm.phone"
                                    type="tel"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse</label>
                                <input
                                    v-model="profileForm.address"
                                    type="text"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ville</label>
                                    <input
                                        v-model="profileForm.city"
                                        type="text"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code postal</label>
                                    <input
                                        v-model="profileForm.postal_code"
                                        type="text"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="profileForm.processing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                                >
                                    <i class="fas fa-save mr-2"></i>
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Password Change -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-lock mr-2 text-yellow-600"></i>
                                Changer le mot de passe
                            </h2>
                        </div>
                        <form @submit.prevent="changePassword" class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mot de passe actuel</label>
                                <input
                                    v-model="passwordForm.current_password"
                                    type="password"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nouveau mot de passe</label>
                                <input
                                    v-model="passwordForm.new_password"
                                    type="password"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmer le mot de passe</label>
                                <input
                                    v-model="passwordForm.new_password_confirmation"
                                    type="password"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="passwordForm.processing"
                                    class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition disabled:opacity-50"
                                >
                                    <i class="fas fa-key mr-2"></i>
                                    Changer le mot de passe
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Avatar Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 text-center">
                        <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-3xl font-bold mx-auto mb-4">
                            {{ initials }}
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            {{ customer.first_name }} {{ customer.last_name }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ customer.email }}</p>
                    </div>

                    <!-- Company Info -->
                    <div v-if="customer.company_name" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-building mr-2 text-gray-400"></i>
                            Entreprise
                        </h3>
                        <div class="space-y-2 text-sm">
                            <p class="text-gray-700 dark:text-gray-300">{{ customer.company_name }}</p>
                            <p v-if="customer.vat_number" class="text-gray-500">
                                TVA: {{ customer.vat_number }}
                            </p>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                            Liens rapides
                        </h3>
                        <div class="space-y-2">
                            <Link
                                :href="route('customer.portal.notification-preferences')"
                                class="flex items-center gap-3 p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <i class="fas fa-bell w-5"></i>
                                Preferences de notification
                            </Link>
                            <Link
                                :href="route('customer.portal.payment-methods')"
                                class="flex items-center gap-3 p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <i class="fas fa-credit-card w-5"></i>
                                Moyens de paiement
                            </Link>
                            <Link
                                :href="route('customer.portal.support.index')"
                                class="flex items-center gap-3 p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <i class="fas fa-headset w-5"></i>
                                Contacter le support
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    customer: Object,
});

const initials = computed(() => {
    const first = props.customer.first_name?.[0] || '';
    const last = props.customer.last_name?.[0] || '';
    return (first + last).toUpperCase();
});

const profileForm = useForm({
    first_name: props.customer.first_name,
    last_name: props.customer.last_name,
    phone: props.customer.phone,
    address: props.customer.address,
    city: props.customer.city,
    postal_code: props.customer.postal_code,
});

const passwordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const updateProfile = () => {
    profileForm.put(route('customer.portal.profile.update'));
};

const changePassword = () => {
    passwordForm.post(route('customer.portal.profile.password'), {
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};
</script>
