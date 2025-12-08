<template>
    <TenantLayout title="Intégrations">
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Intégrations & API</h1>
                        <p class="text-gray-600 mt-1">Connectez Boxibox à vos outils favoris (Zapier, Make, etc.)</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="flex gap-8">
                        <button
                            @click="activeTab = 'webhooks'"
                            :class="[
                                'pb-4 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'webhooks'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Webhooks
                        </button>
                        <button
                            @click="activeTab = 'apikeys'"
                            :class="[
                                'pb-4 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'apikeys'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Clés API
                        </button>
                        <button
                            @click="activeTab = 'marketplace'"
                            :class="[
                                'pb-4 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'marketplace'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            Marketplace
                        </button>
                    </nav>
                </div>

                <!-- Webhooks Tab -->
                <div v-if="activeTab === 'webhooks'">
                    <!-- Info Banner -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6 mb-6 border border-indigo-100">
                        <div class="flex items-start gap-4">
                            <div class="bg-indigo-100 p-3 rounded-lg">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Automatisez avec les Webhooks</h3>
                                <p class="text-gray-600 text-sm">
                                    Les webhooks envoient automatiquement des notifications à vos applications externes lorsque des événements se produisent.
                                    Connectez-vous facilement à <strong>Zapier</strong>, <strong>Make (Integromat)</strong>, <strong>n8n</strong>, ou toute autre plateforme.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Add Webhook Button -->
                    <div class="flex justify-end mb-4">
                        <button
                            @click="showWebhookModal = true; editingWebhook = null; resetWebhookForm()"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Ajouter un Webhook
                        </button>
                    </div>

                    <!-- Webhooks List -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div v-if="webhooks && webhooks.length > 0" class="divide-y divide-gray-100">
                            <div
                                v-for="webhook in webhooks"
                                :key="webhook.id"
                                class="p-6 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ webhook.name }}</h3>
                                            <span
                                                :class="[
                                                    'px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    webhook.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-gray-100 text-gray-600'
                                                ]"
                                            >
                                                {{ webhook.is_active ? 'Actif' : 'Inactif' }}
                                            </span>
                                            <span
                                                v-if="webhook.last_status"
                                                :class="[
                                                    'px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    webhook.last_status === 'success'
                                                        ? 'bg-blue-100 text-blue-700'
                                                        : 'bg-red-100 text-red-700'
                                                ]"
                                            >
                                                {{ webhook.last_status === 'success' ? 'OK' : 'Erreur' }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mb-2 font-mono truncate">{{ webhook.url }}</p>
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <span
                                                v-for="event in webhook.events"
                                                :key="event"
                                                class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded"
                                            >
                                                {{ availableEvents[event] || event }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-gray-500">
                                            <span>{{ webhook.total_calls }} appels</span>
                                            <span>{{ webhook.success_rate }}% réussite</span>
                                            <span v-if="webhook.last_triggered_at">
                                                Dernier: {{ formatDate(webhook.last_triggered_at) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="testWebhook(webhook)"
                                            class="p-2 text-gray-400 hover:text-blue-600 transition-colors"
                                            title="Tester"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="editWebhook(webhook)"
                                            class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
                                            title="Modifier"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteWebhook(webhook)"
                                            class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                                            title="Supprimer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <p class="text-gray-500 mb-4">Aucun webhook configuré</p>
                            <button
                                @click="showWebhookModal = true"
                                class="text-indigo-600 hover:text-indigo-700 font-medium"
                            >
                                Créer votre premier webhook
                            </button>
                        </div>
                    </div>
                </div>

                <!-- API Keys Tab -->
                <div v-if="activeTab === 'apikeys'">
                    <!-- Info Banner -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 mb-6 border border-green-100">
                        <div class="flex items-start gap-4">
                            <div class="bg-green-100 p-3 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Clés API REST</h3>
                                <p class="text-gray-600 text-sm">
                                    Utilisez l'API REST pour intégrer Boxibox directement dans vos applications.
                                    Chaque clé dispose de permissions granulaires pour contrôler l'accès.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Add API Key Button -->
                    <div class="flex justify-end mb-4">
                        <button
                            @click="showApiKeyModal = true; editingApiKey = null; resetApiKeyForm()"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouvelle Clé API
                        </button>
                    </div>

                    <!-- API Keys List -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div v-if="apiKeys && apiKeys.length > 0" class="divide-y divide-gray-100">
                            <div
                                v-for="apiKey in apiKeys"
                                :key="apiKey.id"
                                class="p-6 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ apiKey.name }}</h3>
                                            <span
                                                :class="[
                                                    'px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    apiKey.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-gray-100 text-gray-600'
                                                ]"
                                            >
                                                {{ apiKey.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mb-2 font-mono">{{ apiKey.masked_key }}</p>
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <span
                                                v-for="perm in apiKey.permissions"
                                                :key="perm"
                                                class="bg-green-50 text-green-700 text-xs px-2 py-1 rounded"
                                            >
                                                {{ availablePermissions[perm] || perm }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-4 text-xs text-gray-500">
                                            <span>{{ apiKey.total_requests }} requêtes</span>
                                            <span v-if="apiKey.last_used_at">
                                                Dernière utilisation: {{ formatDate(apiKey.last_used_at) }}
                                            </span>
                                            <span v-if="apiKey.expires_at" :class="isExpired(apiKey.expires_at) ? 'text-red-500' : ''">
                                                {{ isExpired(apiKey.expires_at) ? 'Expirée' : 'Expire: ' + formatDate(apiKey.expires_at) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="editApiKey(apiKey)"
                                            class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
                                            title="Modifier"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteApiKey(apiKey)"
                                            class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                                            title="Supprimer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <p class="text-gray-500 mb-4">Aucune clé API</p>
                            <button
                                @click="showApiKeyModal = true"
                                class="text-green-600 hover:text-green-700 font-medium"
                            >
                                Créer une clé API
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Marketplace Tab -->
                <div v-if="activeTab === 'marketplace'">
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Zapier -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                    <span class="text-2xl font-bold text-orange-600">Z</span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Zapier</h3>
                                    <p class="text-xs text-gray-500">5000+ applications</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Connectez Boxibox à des milliers d'applications sans code. Automatisez vos workflows.
                            </p>
                            <a href="https://zapier.com" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                En savoir plus →
                            </a>
                        </div>

                        <!-- Make -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <span class="text-2xl font-bold text-purple-600">M</span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Make (Integromat)</h3>
                                    <p class="text-xs text-gray-500">Automatisation avancée</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Créez des scénarios d'automatisation complexes avec une interface visuelle puissante.
                            </p>
                            <a href="https://make.com" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                En savoir plus →
                            </a>
                        </div>

                        <!-- n8n -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                    <span class="text-2xl font-bold text-red-600">n8n</span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">n8n</h3>
                                    <p class="text-xs text-gray-500">Self-hosted</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Automatisation open-source que vous pouvez héberger vous-même pour un contrôle total.
                            </p>
                            <a href="https://n8n.io" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                En savoir plus →
                            </a>
                        </div>

                        <!-- Slack -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <span class="text-2xl font-bold text-emerald-600">S</span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Slack</h3>
                                    <p class="text-xs text-gray-500">Notifications</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Recevez les alertes et notifications directement dans vos canaux Slack.
                            </p>
                            <span class="text-sm text-gray-400">Bientôt disponible</span>
                        </div>

                        <!-- Microsoft Teams -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <span class="text-2xl font-bold text-blue-600">T</span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Microsoft Teams</h3>
                                    <p class="text-xs text-gray-500">Notifications</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Intégration native avec Microsoft Teams pour les notifications d'équipe.
                            </p>
                            <span class="text-sm text-gray-400">Bientôt disponible</span>
                        </div>

                        <!-- Custom -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 border-dashed">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Intégration Custom</h3>
                                    <p class="text-xs text-gray-500">API REST</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">
                                Utilisez notre API REST pour créer vos propres intégrations personnalisées.
                            </p>
                            <a href="/docs/api" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                Documentation API →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhook Modal -->
        <Modal v-model="showWebhookModal" :title="editingWebhook ? 'Modifier le Webhook' : 'Nouveau Webhook'">
            <form @submit.prevent="saveWebhook" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input
                        v-model="webhookForm.name"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Mon webhook Zapier"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL du Webhook</label>
                    <input
                        v-model="webhookForm.url"
                        type="url"
                        required
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="https://hooks.zapier.com/..."
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Événements</label>
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                        <label
                            v-for="(label, event) in availableEvents"
                            :key="event"
                            class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded"
                        >
                            <input
                                type="checkbox"
                                :value="event"
                                v-model="webhookForm.events"
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            >
                            <span class="text-sm text-gray-700">{{ label }}</span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="webhookForm.verify_ssl"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        >
                        <span class="text-sm text-gray-700">Vérifier SSL</span>
                    </label>
                    <label v-if="editingWebhook" class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="webhookForm.is_active"
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        >
                        <span class="text-sm text-gray-700">Actif</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        @click="showWebhookModal = false"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="webhookForm.processing"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
                    >
                        {{ editingWebhook ? 'Mettre à jour' : 'Créer' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- API Key Modal -->
        <Modal v-model="showApiKeyModal" :title="editingApiKey ? 'Modifier la Clé API' : 'Nouvelle Clé API'">
            <form @submit.prevent="saveApiKey" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input
                        v-model="apiKeyForm.name"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="Mon application"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                    <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                        <label
                            v-for="(label, perm) in availablePermissions"
                            :key="perm"
                            class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded"
                        >
                            <input
                                type="checkbox"
                                :value="perm"
                                v-model="apiKeyForm.permissions"
                                class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                            >
                            <span class="text-sm text-gray-700">{{ label }}</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration (optionnel)</label>
                    <input
                        v-model="apiKeyForm.expires_at"
                        type="date"
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                </div>
                <div v-if="editingApiKey" class="flex items-center gap-2">
                    <input
                        type="checkbox"
                        v-model="apiKeyForm.is_active"
                        class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                    >
                    <span class="text-sm text-gray-700">Clé active</span>
                </div>
                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        @click="showApiKeyModal = false"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="apiKeyForm.processing"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                    >
                        {{ editingApiKey ? 'Mettre à jour' : 'Créer' }}
                    </button>
                </div>
            </form>
        </Modal>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    webhooks: Array,
    apiKeys: Array,
    availableEvents: Object,
    availablePermissions: Object,
})

const activeTab = ref('webhooks')
const showWebhookModal = ref(false)
const showApiKeyModal = ref(false)
const editingWebhook = ref(null)
const editingApiKey = ref(null)

const webhookForm = useForm({
    name: '',
    url: '',
    events: [],
    verify_ssl: true,
    is_active: true,
})

const apiKeyForm = useForm({
    name: '',
    permissions: [],
    expires_at: '',
    is_active: true,
})

const resetWebhookForm = () => {
    webhookForm.reset()
    webhookForm.events = []
    webhookForm.verify_ssl = true
    webhookForm.is_active = true
}

const resetApiKeyForm = () => {
    apiKeyForm.reset()
    apiKeyForm.permissions = []
    apiKeyForm.is_active = true
}

const editWebhook = (webhook) => {
    editingWebhook.value = webhook
    webhookForm.name = webhook.name
    webhookForm.url = webhook.url
    webhookForm.events = webhook.events || []
    webhookForm.verify_ssl = webhook.verify_ssl
    webhookForm.is_active = webhook.is_active
    showWebhookModal.value = true
}

const saveWebhook = () => {
    if (editingWebhook.value) {
        webhookForm.put(route('tenant.integrations.webhooks.update', editingWebhook.value.id), {
            onSuccess: () => {
                showWebhookModal.value = false
                editingWebhook.value = null
            },
        })
    } else {
        webhookForm.post(route('tenant.integrations.webhooks.store'), {
            onSuccess: () => {
                showWebhookModal.value = false
                resetWebhookForm()
            },
        })
    }
}

const deleteWebhook = (webhook) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce webhook ?')) {
        router.delete(route('tenant.integrations.webhooks.destroy', webhook.id))
    }
}

const testWebhook = (webhook) => {
    router.post(route('tenant.integrations.webhooks.test', webhook.id))
}

const editApiKey = (apiKey) => {
    editingApiKey.value = apiKey
    apiKeyForm.name = apiKey.name
    apiKeyForm.permissions = apiKey.permissions || []
    apiKeyForm.expires_at = apiKey.expires_at?.split('T')[0] || ''
    apiKeyForm.is_active = apiKey.is_active
    showApiKeyModal.value = true
}

const saveApiKey = () => {
    if (editingApiKey.value) {
        apiKeyForm.put(route('tenant.integrations.api-keys.update', editingApiKey.value.id), {
            onSuccess: () => {
                showApiKeyModal.value = false
                editingApiKey.value = null
            },
        })
    } else {
        apiKeyForm.post(route('tenant.integrations.api-keys.store'), {
            onSuccess: () => {
                showApiKeyModal.value = false
                resetApiKeyForm()
            },
        })
    }
}

const deleteApiKey = (apiKey) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette clé API ?')) {
        router.delete(route('tenant.integrations.api-keys.destroy', apiKey.id))
    }
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const isExpired = (date) => {
    if (!date) return false
    return new Date(date) < new Date()
}
</script>
