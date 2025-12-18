<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    user: Object,
    preferences: Object,
})

const form = useForm({
    preferences: {
        theme: props.preferences?.theme || 'system',
        language: props.preferences?.language || 'fr',
        notifications_email: props.preferences?.notifications_email ?? true,
        notifications_push: props.preferences?.notifications_push ?? true,
        dashboard_widgets: props.preferences?.dashboard_widgets || [],
    }
})

const save = () => {
    form.post(route('tenant.user.preferences.update'))
}
</script>

<template>
    <Head title="Mes Préférences" />

    <TenantLayout>
        <div class="p-6 max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Mes Préférences</h1>

            <form @submit.prevent="save" class="space-y-6">
                <!-- Apparence -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Apparence</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Thème</label>
                            <select
                                v-model="form.preferences.theme"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            >
                                <option value="light">Clair</option>
                                <option value="dark">Sombre</option>
                                <option value="system">Système</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Langue</label>
                            <select
                                v-model="form.preferences.language"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                            >
                                <option value="fr">Français</option>
                                <option value="en">English</option>
                                <option value="es">Español</option>
                                <option value="de">Deutsch</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notifications</h2>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.preferences.notifications_email"
                                class="rounded text-indigo-600"
                            />
                            <span class="ml-3 text-gray-700 dark:text-gray-300">Recevoir les notifications par email</span>
                        </label>

                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.preferences.notifications_push"
                                class="rounded text-indigo-600"
                            />
                            <span class="ml-3 text-gray-700 dark:text-gray-300">Activer les notifications push</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
