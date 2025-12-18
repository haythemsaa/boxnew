<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    PlusIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ClockIcon,
    TrashIcon,
    PencilIcon,
    BeakerIcon,
    EyeIcon,
    EyeSlashIcon,
    StarIcon,
    Cog6ToothIcon,
    SignalIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    emailProviders: Array,
    smsProviders: Array,
    availableEmailProviders: Object,
    availableSmsProviders: Object
})

const activeTab = ref('email')
const showAddEmailModal = ref(false)
const showAddSmsModal = ref(false)
const showTestEmailModal = ref(false)
const showTestSmsModal = ref(false)
const selectedProvider = ref(null)
const showPasswords = ref({})

// Email Provider Form
const emailForm = useForm({
    provider: '',
    name: '',
    config: {},
    from_email: '',
    from_name: '',
    reply_to_email: '',
    is_default: false,
    daily_limit: null,
    monthly_limit: null
})

// SMS Provider Form
const smsForm = useForm({
    provider: '',
    name: '',
    config: {},
    from_number: '',
    from_name: '',
    is_default: false,
    daily_limit: null,
    monthly_limit: null
})

// Test Forms
const testEmailForm = useForm({
    test_email: ''
})

const testSmsForm = useForm({
    test_phone: ''
})

// Computed
const selectedEmailProviderInfo = computed(() => {
    return emailForm.provider ? props.availableEmailProviders[emailForm.provider] : null
})

const selectedSmsProviderInfo = computed(() => {
    return smsForm.provider ? props.availableSmsProviders[smsForm.provider] : null
})

// Methods
function openAddEmailModal() {
    emailForm.reset()
    emailForm.provider = ''
    emailForm.config = {}
    showAddEmailModal.value = true
}

function openAddSmsModal() {
    smsForm.reset()
    smsForm.provider = ''
    smsForm.config = {}
    showAddSmsModal.value = true
}

function onEmailProviderChange() {
    emailForm.config = {}
    const info = props.availableEmailProviders[emailForm.provider]
    if (info) {
        emailForm.name = info.name
        // Initialize config with default values
        Object.entries(info.fields || {}).forEach(([key, field]) => {
            emailForm.config[key] = field.default || ''
        })
    }
}

function onSmsProviderChange() {
    smsForm.config = {}
    const info = props.availableSmsProviders[smsForm.provider]
    if (info) {
        smsForm.name = info.name
        Object.entries(info.fields || {}).forEach(([key, field]) => {
            smsForm.config[key] = field.default || ''
        })
    }
}

function submitEmailProvider() {
    emailForm.post(route('tenant.settings.communication-providers.email.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddEmailModal.value = false
            emailForm.reset()
        }
    })
}

function submitSmsProvider() {
    smsForm.post(route('tenant.settings.communication-providers.sms.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddSmsModal.value = false
            smsForm.reset()
        }
    })
}

function openTestEmailModal(provider) {
    selectedProvider.value = provider
    testEmailForm.reset()
    showTestEmailModal.value = true
}

function openTestSmsModal(provider) {
    selectedProvider.value = provider
    testSmsForm.reset()
    showTestSmsModal.value = true
}

function submitTestEmail() {
    testEmailForm.post(route('tenant.settings.communication-providers.email.test', selectedProvider.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showTestEmailModal.value = false
        }
    })
}

function submitTestSms() {
    testSmsForm.post(route('tenant.settings.communication-providers.sms.test', selectedProvider.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showTestSmsModal.value = false
        }
    })
}

function deleteEmailProvider(provider) {
    if (confirm(`Supprimer le fournisseur "${provider.name}" ?`)) {
        router.delete(route('tenant.settings.communication-providers.email.destroy', provider.id), {
            preserveScroll: true
        })
    }
}

function deleteSmsProvider(provider) {
    if (confirm(`Supprimer le fournisseur "${provider.name}" ?`)) {
        router.delete(route('tenant.settings.communication-providers.sms.destroy', provider.id), {
            preserveScroll: true
        })
    }
}

function setDefaultEmailProvider(provider) {
    router.put(route('tenant.settings.communication-providers.email.update', provider.id), {
        ...provider,
        is_default: true
    }, {
        preserveScroll: true
    })
}

function setDefaultSmsProvider(provider) {
    router.put(route('tenant.settings.communication-providers.sms.update', provider.id), {
        ...provider,
        is_default: true
    }, {
        preserveScroll: true
    })
}

function togglePassword(key) {
    showPasswords.value[key] = !showPasswords.value[key]
}

function getStatusIcon(provider) {
    if (provider.is_verified) return CheckCircleIcon
    if (provider.verification_status === 'failed') return ExclamationCircleIcon
    return ClockIcon
}

function getStatusColor(provider) {
    if (provider.is_verified) return 'text-green-500'
    if (provider.verification_status === 'failed') return 'text-red-500'
    return 'text-yellow-500'
}

function getStatusText(provider) {
    if (provider.is_verified) return 'Verifie'
    if (provider.verification_status === 'failed') return 'Echec'
    return 'En attente'
}
</script>

<template>
    <TenantLayout title="Fournisseurs Email & SMS">
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 text-white">
                <h1 class="text-2xl font-bold">Fournisseurs de Communication</h1>
                <p class="mt-2 text-indigo-100">
                    Configurez vos propres API keys pour envoyer des emails et SMS avec votre compte.
                </p>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px">
                        <button
                            @click="activeTab = 'email'"
                            :class="[
                                'flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'email'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <EnvelopeIcon class="h-5 w-5" />
                            Fournisseurs Email
                            <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full">
                                {{ emailProviders?.length || 0 }}
                            </span>
                        </button>
                        <button
                            @click="activeTab = 'sms'"
                            :class="[
                                'flex items-center gap-2 px-6 py-4 border-b-2 font-medium text-sm transition-colors',
                                activeTab === 'sms'
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <DevicePhoneMobileIcon class="h-5 w-5" />
                            Fournisseurs SMS
                            <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full">
                                {{ smsProviders?.length || 0 }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Email Providers Tab -->
                <div v-if="activeTab === 'email'" class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Vos fournisseurs email
                        </h2>
                        <button
                            @click="openAddEmailModal"
                            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Ajouter
                        </button>
                    </div>

                    <!-- Email Providers List -->
                    <div v-if="emailProviders?.length" class="space-y-4">
                        <div
                            v-for="provider in emailProviders"
                            :key="provider.id"
                            class="border dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-4">
                                    <!-- Provider Logo -->
                                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <EnvelopeIcon class="h-6 w-6 text-gray-500" />
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                                {{ provider.name }}
                                            </h3>
                                            <span v-if="provider.is_default" class="flex items-center gap-1 text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">
                                                <StarIcon class="h-3 w-3" />
                                                Par defaut
                                            </span>
                                            <span :class="['flex items-center gap-1 text-xs px-2 py-0.5 rounded-full', getStatusColor(provider)]">
                                                <component :is="getStatusIcon(provider)" class="h-3 w-3" />
                                                {{ getStatusText(provider) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ provider.provider_info?.name }} - {{ provider.from_email }}
                                        </p>
                                        <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                            <span>{{ provider.emails_sent_today }} emails aujourd'hui</span>
                                            <span>{{ provider.emails_sent_month }} ce mois</span>
                                            <span v-if="provider.daily_limit">Limite: {{ provider.daily_limit }}/jour</span>
                                        </div>
                                        <p v-if="provider.last_error" class="mt-2 text-xs text-red-500">
                                            Erreur: {{ provider.last_error }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="!provider.is_default"
                                        @click="setDefaultEmailProvider(provider)"
                                        class="p-2 text-gray-400 hover:text-yellow-500 transition-colors"
                                        title="Definir par defaut"
                                    >
                                        <StarIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="openTestEmailModal(provider)"
                                        class="p-2 text-gray-400 hover:text-indigo-500 transition-colors"
                                        title="Tester"
                                    >
                                        <BeakerIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="deleteEmailProvider(provider)"
                                        class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Webhook URL -->
                            <div v-if="provider.webhook_url" class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <p class="text-xs text-gray-500 mb-1">URL Webhook (a configurer chez {{ provider.provider_info?.name }})</p>
                                <code class="text-xs text-indigo-600 dark:text-indigo-400 break-all">
                                    {{ provider.webhook_url }}
                                </code>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun fournisseur email</h3>
                        <p class="mt-1 text-sm text-gray-500">Ajoutez votre premier fournisseur email pour envoyer des messages.</p>
                        <button
                            @click="openAddEmailModal"
                            class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            Ajouter un fournisseur
                        </button>
                    </div>
                </div>

                <!-- SMS Providers Tab -->
                <div v-if="activeTab === 'sms'" class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Vos fournisseurs SMS
                        </h2>
                        <button
                            @click="openAddSmsModal"
                            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Ajouter
                        </button>
                    </div>

                    <!-- SMS Providers List -->
                    <div v-if="smsProviders?.length" class="space-y-4">
                        <div
                            v-for="provider in smsProviders"
                            :key="provider.id"
                            class="border dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <DevicePhoneMobileIcon class="h-6 w-6 text-gray-500" />
                                    </div>

                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                                {{ provider.name }}
                                            </h3>
                                            <span v-if="provider.is_default" class="flex items-center gap-1 text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">
                                                <StarIcon class="h-3 w-3" />
                                                Par defaut
                                            </span>
                                            <span :class="['flex items-center gap-1 text-xs px-2 py-0.5 rounded-full', getStatusColor(provider)]">
                                                <component :is="getStatusIcon(provider)" class="h-3 w-3" />
                                                {{ getStatusText(provider) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ provider.provider_info?.name }} - {{ provider.sender_id }}
                                        </p>
                                        <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                            <span>{{ provider.sms_sent_today }} SMS aujourd'hui</span>
                                            <span>{{ provider.sms_sent_month }} ce mois</span>
                                            <span v-if="provider.balance !== null">Solde: {{ provider.balance }} {{ provider.balance_currency }}</span>
                                        </div>
                                        <p v-if="provider.last_error" class="mt-2 text-xs text-red-500">
                                            Erreur: {{ provider.last_error }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="!provider.is_default"
                                        @click="setDefaultSmsProvider(provider)"
                                        class="p-2 text-gray-400 hover:text-yellow-500 transition-colors"
                                        title="Definir par defaut"
                                    >
                                        <StarIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="openTestSmsModal(provider)"
                                        class="p-2 text-gray-400 hover:text-indigo-500 transition-colors"
                                        title="Tester"
                                    >
                                        <BeakerIcon class="h-5 w-5" />
                                    </button>
                                    <button
                                        @click="deleteSmsProvider(provider)"
                                        class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Webhook URL -->
                            <div v-if="provider.webhook_url && provider.provider_info?.supports_inbound" class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <p class="text-xs text-gray-500 mb-1">URL Webhook (pour delivery reports et SMS entrants)</p>
                                <code class="text-xs text-indigo-600 dark:text-indigo-400 break-all">
                                    {{ provider.webhook_url }}
                                </code>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <DevicePhoneMobileIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun fournisseur SMS</h3>
                        <p class="mt-1 text-sm text-gray-500">Ajoutez votre premier fournisseur SMS pour envoyer des messages.</p>
                        <button
                            @click="openAddSmsModal"
                            class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            Ajouter un fournisseur
                        </button>
                    </div>
                </div>
            </div>

            <!-- Available Providers Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                    {{ activeTab === 'email' ? 'Fournisseurs Email Supportes' : 'Fournisseurs SMS Supportes' }}
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <template v-if="activeTab === 'email'">
                        <div
                            v-for="(info, key) in availableEmailProviders"
                            :key="key"
                            class="text-center p-4 rounded-lg border dark:border-gray-700 hover:border-indigo-500 transition-colors"
                        >
                            <EnvelopeIcon class="h-8 w-8 mx-auto text-gray-400 mb-2" />
                            <p class="font-medium text-sm text-gray-900 dark:text-white">{{ info.name }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ info.description?.substring(0, 40) }}...</p>
                        </div>
                    </template>
                    <template v-else>
                        <div
                            v-for="(info, key) in availableSmsProviders"
                            :key="key"
                            class="text-center p-4 rounded-lg border dark:border-gray-700 hover:border-indigo-500 transition-colors"
                        >
                            <DevicePhoneMobileIcon class="h-8 w-8 mx-auto text-gray-400 mb-2" />
                            <p class="font-medium text-sm text-gray-900 dark:text-white">{{ info.name }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ info.description?.substring(0, 40) }}...</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Add Email Provider Modal -->
        <Teleport to="body">
            <div v-if="showAddEmailModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddEmailModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Ajouter un fournisseur Email
                        </h3>

                        <form @submit.prevent="submitEmailProvider" class="space-y-4">
                            <!-- Provider Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Fournisseur
                                </label>
                                <select
                                    v-model="emailForm.provider"
                                    @change="onEmailProviderChange"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    required
                                >
                                    <option value="">Choisir un fournisseur</option>
                                    <option v-for="(info, key) in availableEmailProviders" :key="key" :value="key">
                                        {{ info.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Dynamic Config Fields -->
                            <template v-if="selectedEmailProviderInfo">
                                <div v-for="(field, key) in selectedEmailProviderInfo.fields" :key="key">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </label>
                                    <div v-if="field.type === 'password'" class="relative">
                                        <input
                                            :type="showPasswords[key] ? 'text' : 'password'"
                                            v-model="emailForm.config[key]"
                                            :placeholder="field.placeholder"
                                            :required="field.required"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 pr-10"
                                        />
                                        <button
                                            type="button"
                                            @click="togglePassword(key)"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            <EyeIcon v-if="!showPasswords[key]" class="h-5 w-5" />
                                            <EyeSlashIcon v-else class="h-5 w-5" />
                                        </button>
                                    </div>
                                    <select
                                        v-else-if="field.type === 'select'"
                                        v-model="emailForm.config[key]"
                                        :required="field.required"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <option v-for="(label, val) in field.options" :key="val" :value="val">
                                            {{ label }}
                                        </option>
                                    </select>
                                    <input
                                        v-else
                                        :type="field.type"
                                        v-model="emailForm.config[key]"
                                        :placeholder="field.placeholder"
                                        :required="field.required"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>

                                <!-- Sender Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Email expediteur <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            v-model="emailForm.from_email"
                                            placeholder="noreply@votre-domaine.com"
                                            required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Nom expediteur <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="emailForm.from_name"
                                            placeholder="Ma Societe"
                                            required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Email de reponse (optionnel)
                                    </label>
                                    <input
                                        type="email"
                                        v-model="emailForm.reply_to_email"
                                        placeholder="contact@votre-domaine.com"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="email_is_default"
                                        v-model="emailForm.is_default"
                                        class="rounded border-gray-300"
                                    />
                                    <label for="email_is_default" class="text-sm text-gray-700 dark:text-gray-300">
                                        Definir comme fournisseur par defaut
                                    </label>
                                </div>
                            </template>

                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showAddEmailModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="emailForm.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <ArrowPathIcon v-if="emailForm.processing" class="h-5 w-5 animate-spin" />
                                    <span v-else>Ajouter</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Add SMS Provider Modal -->
        <Teleport to="body">
            <div v-if="showAddSmsModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddSmsModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Ajouter un fournisseur SMS
                        </h3>

                        <form @submit.prevent="submitSmsProvider" class="space-y-4">
                            <!-- Provider Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Fournisseur
                                </label>
                                <select
                                    v-model="smsForm.provider"
                                    @change="onSmsProviderChange"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    required
                                >
                                    <option value="">Choisir un fournisseur</option>
                                    <option v-for="(info, key) in availableSmsProviders" :key="key" :value="key">
                                        {{ info.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Dynamic Config Fields -->
                            <template v-if="selectedSmsProviderInfo">
                                <div v-for="(field, key) in selectedSmsProviderInfo.fields" :key="key">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-500">*</span>
                                    </label>
                                    <div v-if="field.type === 'password'" class="relative">
                                        <input
                                            :type="showPasswords[key] ? 'text' : 'password'"
                                            v-model="smsForm.config[key]"
                                            :placeholder="field.placeholder"
                                            :required="field.required"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 pr-10"
                                        />
                                        <button
                                            type="button"
                                            @click="togglePassword(key)"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            <EyeIcon v-if="!showPasswords[key]" class="h-5 w-5" />
                                            <EyeSlashIcon v-else class="h-5 w-5" />
                                        </button>
                                    </div>
                                    <input
                                        v-else
                                        :type="field.type"
                                        v-model="smsForm.config[key]"
                                        :placeholder="field.placeholder"
                                        :required="field.required"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                    />
                                </div>

                                <!-- Sender Info -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div v-if="selectedSmsProviderInfo.sender_type !== 'alphanumeric'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Numero expediteur
                                        </label>
                                        <input
                                            type="tel"
                                            v-model="smsForm.from_number"
                                            placeholder="+33612345678"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                    <div v-if="selectedSmsProviderInfo.sender_type !== 'phone'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Nom expediteur (max 11 car.)
                                        </label>
                                        <input
                                            type="text"
                                            v-model="smsForm.from_name"
                                            placeholder="MaSociete"
                                            maxlength="11"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="sms_is_default"
                                        v-model="smsForm.is_default"
                                        class="rounded border-gray-300"
                                    />
                                    <label for="sms_is_default" class="text-sm text-gray-700 dark:text-gray-300">
                                        Definir comme fournisseur par defaut
                                    </label>
                                </div>
                            </template>

                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showAddSmsModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="smsForm.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <ArrowPathIcon v-if="smsForm.processing" class="h-5 w-5 animate-spin" />
                                    <span v-else>Ajouter</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Test Email Modal -->
        <Teleport to="body">
            <div v-if="showTestEmailModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showTestEmailModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Tester {{ selectedProvider?.name }}
                        </h3>

                        <form @submit.prevent="submitTestEmail" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Email de test
                                </label>
                                <input
                                    type="email"
                                    v-model="testEmailForm.test_email"
                                    placeholder="votre@email.com"
                                    required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Un email de test sera envoye a cette adresse.
                                </p>
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showTestEmailModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="testEmailForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <ArrowPathIcon v-if="testEmailForm.processing" class="h-5 w-5 animate-spin" />
                                    <BeakerIcon v-else class="h-5 w-5" />
                                    Envoyer le test
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Test SMS Modal -->
        <Teleport to="body">
            <div v-if="showTestSmsModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showTestSmsModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Tester {{ selectedProvider?.name }}
                        </h3>

                        <form @submit.prevent="submitTestSms" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Numero de test
                                </label>
                                <input
                                    type="tel"
                                    v-model="testSmsForm.test_phone"
                                    placeholder="+33612345678"
                                    required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Un SMS de test sera envoye a ce numero. Format international recommande.
                                </p>
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button
                                    type="button"
                                    @click="showTestSmsModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="testSmsForm.processing"
                                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <ArrowPathIcon v-if="testSmsForm.processing" class="h-5 w-5 animate-spin" />
                                    <BeakerIcon v-else class="h-5 w-5" />
                                    Envoyer le test
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>
