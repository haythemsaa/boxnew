<script setup>
/**
 * Notification Settings Page - Configure email, SMS, and push notifications
 */
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    BellIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    BellAlertIcon,
    Cog6ToothIcon,
    CheckCircleIcon,
    XCircleIcon,
    PaperAirplaneIcon,
    ChartBarIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    preferences: Object,
    notificationTypes: Array,
    logs: Array,
    stats: Object,
    channels: Object,
})

// State
const selectedTab = ref('preferences')
const showTestModal = ref(false)
const testChannel = ref('email')
const testRecipient = ref('')

// Form for preferences
const form = useForm({
    email_enabled: props.channels?.email?.enabled ?? true,
    sms_enabled: props.channels?.sms?.enabled ?? false,
    push_enabled: props.channels?.push?.enabled ?? false,
    types: props.notificationTypes?.reduce((acc, type) => {
        acc[type.id] = {
            email: type.email,
            sms: type.sms,
            push: type.push,
        }
        return acc
    }, {}) || {},
})

// Save preferences
const savePreferences = () => {
    form.post('/tenant/notifications/preferences', {
        preserveScroll: true,
    })
}

// Send test notification
const sendTest = () => {
    router.post('/tenant/notifications/test', {
        channel: testChannel.value,
        recipient: testRecipient.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showTestModal.value = false
            testRecipient.value = ''
        },
    })
}

// Tabs
const tabs = [
    { id: 'preferences', name: 'Préférences', icon: Cog6ToothIcon },
    { id: 'logs', name: 'Historique', icon: ClockIcon },
]

// Helpers
const getChannelIcon = (channel) => {
    switch (channel) {
        case 'email': return EnvelopeIcon
        case 'sms': return DevicePhoneMobileIcon
        case 'push': return BellAlertIcon
        default: return BellIcon
    }
}

const getStatusColor = (status) => {
    switch (status) {
        case 'sent': return 'bg-green-100 text-green-700'
        case 'failed': return 'bg-red-100 text-red-700'
        case 'pending': return 'bg-amber-100 text-amber-700'
        default: return 'bg-gray-100 text-gray-700'
    }
}

const formatTimeAgo = (date) => {
    if (!date) return ''
    const seconds = Math.floor((new Date() - new Date(date)) / 1000)
    if (seconds < 60) return 'à l\'instant'
    if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`
    if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)}h`
    return `il y a ${Math.floor(seconds / 86400)}j`
}
</script>

<template>
    <TenantLayout>
        <Head title="Notifications" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                    <p class="text-gray-500 mt-1">
                        Configurez vos canaux de notification et consultez l'historique
                    </p>
                </div>
                <button
                    @click="showTestModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors"
                >
                    <PaperAirplaneIcon class="h-5 w-5" />
                    Envoyer un test
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="h-5 w-5 text-green-600" />
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ stats?.total_sent || 0 }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Total envoyées</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="h-5 w-5 text-blue-600" />
                        </div>
                        <span class="text-2xl font-bold text-blue-600">{{ stats?.sent_today || 0 }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Aujourd'hui</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <XCircleIcon class="h-5 w-5 text-red-600" />
                        </div>
                        <span class="text-2xl font-bold text-red-600">{{ stats?.failed || 0 }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Échecs</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <ClockIcon class="h-5 w-5 text-amber-600" />
                        </div>
                        <span class="text-2xl font-bold text-amber-600">{{ stats?.pending || 0 }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">En attente</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex gap-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="selectedTab = tab.id"
                        :class="[
                            'flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm transition-colors',
                            selectedTab === tab.id
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <component :is="tab.icon" class="h-5 w-5" />
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Preferences Tab -->
            <div v-if="selectedTab === 'preferences'" class="space-y-6">
                <!-- Channel Configuration -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Canaux de notification</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Email Channel -->
                        <div :class="['p-4 rounded-xl border-2 transition-colors', form.email_enabled ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-gray-50']">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', form.email_enabled ? 'bg-indigo-100' : 'bg-gray-200']">
                                        <EnvelopeIcon :class="['h-5 w-5', form.email_enabled ? 'text-indigo-600' : 'text-gray-400']" />
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Email</h4>
                                        <p class="text-xs text-gray-500">
                                            {{ channels?.email?.configured ? 'Configuré' : 'Non configuré' }}
                                        </p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.email_enabled" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <Link
                                href="/tenant/notifications/email-settings"
                                class="text-sm text-indigo-600 hover:underline"
                            >
                                Configurer l'email
                            </Link>
                        </div>

                        <!-- SMS Channel -->
                        <div :class="['p-4 rounded-xl border-2 transition-colors', form.sms_enabled ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-gray-50']">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', form.sms_enabled ? 'bg-indigo-100' : 'bg-gray-200']">
                                        <DevicePhoneMobileIcon :class="['h-5 w-5', form.sms_enabled ? 'text-indigo-600' : 'text-gray-400']" />
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">SMS</h4>
                                        <p class="text-xs text-gray-500">
                                            {{ channels?.sms?.configured ? 'Configuré' : 'Non configuré' }}
                                        </p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.sms_enabled" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <Link
                                href="/tenant/notifications/sms-settings"
                                class="text-sm text-indigo-600 hover:underline"
                            >
                                Configurer le SMS
                            </Link>
                        </div>

                        <!-- Push Channel -->
                        <div :class="['p-4 rounded-xl border-2 transition-colors', form.push_enabled ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-gray-50']">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', form.push_enabled ? 'bg-indigo-100' : 'bg-gray-200']">
                                        <BellAlertIcon :class="['h-5 w-5', form.push_enabled ? 'text-indigo-600' : 'text-gray-400']" />
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Push</h4>
                                        <p class="text-xs text-gray-500">
                                            {{ channels?.push?.configured ? 'Configuré' : 'Non configuré' }}
                                        </p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.push_enabled" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <Link
                                href="/tenant/notifications/push-settings"
                                class="text-sm text-indigo-600 hover:underline"
                            >
                                Configurer le push
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Notification Types -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Types de notifications</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Notification
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                        <EnvelopeIcon class="h-4 w-4 mx-auto" />
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                        <DevicePhoneMobileIcon class="h-4 w-4 mx-auto" />
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                        <BellAlertIcon class="h-4 w-4 mx-auto" />
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="type in notificationTypes" :key="type.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ type.name }}</p>
                                            <p class="text-xs text-gray-500">{{ type.description }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.types[type.id].email"
                                            :disabled="!form.email_enabled"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 disabled:opacity-50"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.types[type.id].sms"
                                            :disabled="!form.sms_enabled"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 disabled:opacity-50"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.types[type.id].push"
                                            :disabled="!form.push_enabled"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 disabled:opacity-50"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button
                            @click="savePreferences"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer les préférences' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Logs Tab -->
            <div v-else-if="selectedTab === 'logs'" class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900">Historique des notifications</h3>
                        <Link
                            href="/tenant/notifications/logs"
                            class="text-sm text-indigo-600 hover:underline"
                        >
                            Voir tout l'historique
                        </Link>
                    </div>

                    <div v-if="logs?.length" class="space-y-3">
                        <div
                            v-for="log in logs.slice(0, 20)"
                            :key="log.id"
                            class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg"
                        >
                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', log.status === 'sent' ? 'bg-green-100' : log.status === 'failed' ? 'bg-red-100' : 'bg-amber-100']">
                                <component
                                    :is="getChannelIcon(log.channel)"
                                    :class="['h-5 w-5', log.status === 'sent' ? 'text-green-600' : log.status === 'failed' ? 'text-red-600' : 'text-amber-600']"
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ log.subject || log.type }}</p>
                                <p class="text-sm text-gray-500 truncate">{{ log.recipient }}</p>
                            </div>
                            <div class="text-right">
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(log.status)]">
                                    {{ log.status }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">{{ formatTimeAgo(log.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <BellIcon class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                        <p class="text-gray-500">Aucune notification envoyée</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Modal -->
        <div v-if="showTestModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Envoyer une notification test</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Canal</label>
                        <select
                            v-model="testChannel"
                            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="push">Push</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ testChannel === 'email' ? 'Adresse email' : testChannel === 'sms' ? 'Numéro de téléphone' : 'Token FCM' }}
                        </label>
                        <input
                            type="text"
                            v-model="testRecipient"
                            :placeholder="testChannel === 'email' ? 'email@example.com' : testChannel === 'sms' ? '+33612345678' : 'fcm_token...'"
                            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button
                        @click="showTestModal = false"
                        class="flex-1 px-4 py-2 border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50"
                    >
                        Annuler
                    </button>
                    <button
                        @click="sendTest"
                        :disabled="!testRecipient"
                        class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Envoyer
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
