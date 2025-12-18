<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Support</h1>
                <p class="text-gray-600 dark:text-gray-400">Nous sommes la pour vous aider</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Request Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-paper-plane mr-2 text-blue-600"></i>
                                Envoyer une demande
                            </h2>
                        </div>
                        <form @submit.prevent="submitRequest" class="p-6 space-y-4">
                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type de demande</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        v-for="type in requestTypes"
                                        :key="type.value"
                                        type="button"
                                        @click="form.type = type.value"
                                        :class="[
                                            'p-4 rounded-xl border-2 text-left transition',
                                            form.type === type.value
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                        ]"
                                    >
                                        <i :class="type.icon" class="text-xl mb-2"></i>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ type.label }}</div>
                                        <div class="text-xs text-gray-500">{{ type.description }}</div>
                                    </button>
                                </div>
                            </div>

                            <!-- Contract -->
                            <div v-if="contracts.length > 0">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contrat concerne (optionnel)</label>
                                <select
                                    v-model="form.contract_id"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                >
                                    <option :value="null">Aucun contrat specifique</option>
                                    <option v-for="contract in contracts" :key="contract.id" :value="contract.id">
                                        {{ contract.contract_number }}
                                    </option>
                                </select>
                            </div>

                            <!-- Subject -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sujet</label>
                                <input
                                    v-model="form.subject"
                                    type="text"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    placeholder="Resumez votre demande"
                                    required
                                />
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="5"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                    placeholder="Decrivez votre demande en detail..."
                                    required
                                ></textarea>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priorite</label>
                                <select
                                    v-model="form.priority"
                                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="low">Basse - Pas urgent</option>
                                    <option value="medium">Moyenne - Normal</option>
                                    <option value="high">Haute - Urgent</option>
                                </select>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                                >
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Envoyer la demande
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Contact Info Sidebar -->
                <div class="space-y-6">
                    <!-- Contact Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-phone-alt mr-2 text-green-600"></i>
                            Nous contacter
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-phone text-green-600"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Telephone</div>
                                    <a href="tel:+33123456789" class="font-medium text-gray-900 dark:text-white hover:text-blue-600">
                                        01 23 45 67 89
                                    </a>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-envelope text-blue-600"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-500">Email</div>
                                    <a href="mailto:support@boxibox.fr" class="font-medium text-gray-900 dark:text-white hover:text-blue-600">
                                        support@boxibox.fr
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hours -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-clock mr-2 text-purple-600"></i>
                            Horaires d'ouverture
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Lundi - Vendredi</span>
                                <span class="font-medium text-gray-900 dark:text-white">9h - 18h</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Samedi</span>
                                <span class="font-medium text-gray-900 dark:text-white">9h - 12h</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Dimanche</span>
                                <span class="font-medium text-gray-500">Ferme</span>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Link -->
                    <div class="bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl p-6 text-white">
                        <h3 class="font-semibold mb-2">
                            <i class="fas fa-question-circle mr-2"></i>
                            Questions frequentes
                        </h3>
                        <p class="text-blue-100 text-sm mb-4">
                            Trouvez rapidement des reponses a vos questions.
                        </p>
                        <a href="#" class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition text-sm">
                            Voir la FAQ
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    contracts: Array,
});

const requestTypes = [
    {
        value: 'general',
        label: 'Question generale',
        description: 'Informations, renseignements',
        icon: 'fas fa-comment-dots text-purple-600',
    },
    {
        value: 'maintenance',
        label: 'Maintenance',
        description: 'Probleme technique',
        icon: 'fas fa-tools text-orange-600',
    },
    {
        value: 'box_change',
        label: 'Changement de box',
        description: 'Agrandir ou reduire',
        icon: 'fas fa-exchange-alt text-blue-600',
    },
    {
        value: 'termination',
        label: 'Resiliation',
        description: 'Fin de contrat',
        icon: 'fas fa-door-open text-red-600',
    },
];

const form = useForm({
    type: 'general',
    contract_id: null,
    subject: '',
    description: '',
    priority: 'medium',
});

const submitRequest = () => {
    form.post(route('customer.portal.submit-request'), {
        onSuccess: () => {
            form.reset('subject', 'description');
        },
    });
};
</script>
