<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Preferences de notification</h1>
                <p class="text-gray-600 dark:text-gray-400">Gerez comment vous souhaitez etre contacte</p>
            </div>

            <form @submit.prevent="savePreferences" class="space-y-6">
                <!-- Email Notifications -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            <i class="fas fa-envelope mr-2 text-blue-600"></i>
                            Notifications par email
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Factures</div>
                                <div class="text-sm text-gray-500">Recevoir les nouvelles factures par email</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.email_invoices" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Rappels de paiement</div>
                                <div class="text-sm text-gray-500">Rappels avant echeance des factures</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.email_reminders" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Communications marketing</div>
                                <div class="text-sm text-gray-500">Offres speciales et promotions</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.email_marketing" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SMS Notifications -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            <i class="fas fa-sms mr-2 text-green-600"></i>
                            Notifications par SMS
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Factures</div>
                                <div class="text-sm text-gray-500">Notification SMS lors d'une nouvelle facture</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.sms_invoices" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Rappels urgents</div>
                                <div class="text-sm text-gray-500">SMS pour les paiements en retard</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.sms_reminders" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Push Notifications -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            <i class="fas fa-bell mr-2 text-purple-600"></i>
                            Notifications push
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Activer les notifications push</div>
                                <div class="text-sm text-gray-500">Recevoir des notifications sur votre navigateur</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input v-model="form.push_enabled" type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les preferences
                    </button>
                </div>
            </form>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    preferences: Object,
});

const form = useForm({
    email_invoices: props.preferences?.email_invoices ?? true,
    email_reminders: props.preferences?.email_reminders ?? true,
    email_marketing: props.preferences?.email_marketing ?? false,
    sms_invoices: props.preferences?.sms_invoices ?? false,
    sms_reminders: props.preferences?.sms_reminders ?? true,
    push_enabled: props.preferences?.push_enabled ?? true,
});

const savePreferences = () => {
    form.put(route('customer.portal.notification-preferences.update'));
};
</script>
