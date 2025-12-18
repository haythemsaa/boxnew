<template>
    <TenantLayout title="Documentation API">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="text-3xl">📚</span>
                        Documentation API
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Integrez BoxiBox dans vos applications
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        href="/docs/api"
                        target="_blank"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        OpenAPI Docs
                    </a>
                    <Link
                        href="/tenant/integrations/webhooks"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        Configurer Webhooks
                    </Link>
                </div>
            </div>

            <!-- Quick Start Guide -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <span class="text-2xl">1️⃣</span>
                        </div>
                        <h3 class="text-lg font-bold">Obtenir une cle API</h3>
                    </div>
                    <p class="text-blue-100 text-sm mb-4">
                        Creez une cle API depuis la section Integrations pour authentifier vos requetes.
                    </p>
                    <Link
                        href="/tenant/integrations"
                        class="text-sm font-medium bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg inline-block"
                    >
                        Creer une cle →
                    </Link>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <span class="text-2xl">2️⃣</span>
                        </div>
                        <h3 class="text-lg font-bold">Configurer webhooks</h3>
                    </div>
                    <p class="text-green-100 text-sm mb-4">
                        Recevez des notifications en temps reel sur les evenements BoxiBox.
                    </p>
                    <Link
                        href="/tenant/integrations/webhooks"
                        class="text-sm font-medium bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg inline-block"
                    >
                        Configurer →
                    </Link>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <span class="text-2xl">3️⃣</span>
                        </div>
                        <h3 class="text-lg font-bold">Tester l'API</h3>
                    </div>
                    <p class="text-purple-100 text-sm mb-4">
                        Utilisez notre documentation interactive pour tester les endpoints.
                    </p>
                    <a
                        href="/docs/api"
                        target="_blank"
                        class="text-sm font-medium bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg inline-block"
                    >
                        Explorer →
                    </a>
                </div>
            </div>

            <!-- Authentication Section -->
            <div class="bg-white rounded-xl border mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="text-xl">🔐</span>
                        Authentification
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-4">
                        L'API BoxiBox utilise des cles API pour l'authentification. Incluez votre cle dans chaque requete via le header <code class="bg-gray-100 px-2 py-1 rounded text-sm">Authorization</code>.
                    </p>

                    <div class="bg-gray-900 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-400">HEADER</span>
                            <button
                                @click="copyCode('Authorization: Bearer YOUR_API_KEY')"
                                class="text-xs text-gray-400 hover:text-white"
                            >
                                Copier
                            </button>
                        </div>
                        <code class="text-green-400 text-sm font-mono">Authorization: Bearer YOUR_API_KEY</code>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex items-start gap-2">
                            <span class="text-amber-500">⚠️</span>
                            <div>
                                <p class="text-sm font-medium text-amber-800">Securite</p>
                                <p class="text-sm text-amber-700">
                                    Ne partagez jamais votre cle API. Utilisez des variables d'environnement pour la stocker.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Endpoints -->
            <div class="bg-white rounded-xl border mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="text-xl">🔗</span>
                        Endpoints principaux
                    </h2>
                </div>
                <div class="divide-y">
                    <div v-for="(endpoints, category) in apiEndpoints" :key="category">
                        <button
                            @click="toggleCategory(category)"
                            class="w-full p-4 flex items-center justify-between hover:bg-gray-50"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ getCategoryIcon(category) }}</span>
                                <span class="font-medium text-gray-900">{{ category }}</span>
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                    {{ endpoints.length }} endpoints
                                </span>
                            </div>
                            <svg
                                class="w-5 h-5 text-gray-400 transition-transform"
                                :class="{ 'rotate-180': expandedCategories.includes(category) }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-if="expandedCategories.includes(category)" class="px-4 pb-4 bg-gray-50">
                            <div class="space-y-2">
                                <div
                                    v-for="endpoint in endpoints"
                                    :key="endpoint.path"
                                    class="bg-white rounded-lg p-4 border"
                                >
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            class="px-2 py-0.5 text-xs font-bold rounded"
                                            :class="getMethodClass(endpoint.method)"
                                        >
                                            {{ endpoint.method }}
                                        </span>
                                        <code class="text-sm font-mono text-gray-700">{{ endpoint.path }}</code>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ endpoint.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Code Examples -->
            <div class="bg-white rounded-xl border mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="text-xl">💻</span>
                        Exemples de code
                    </h2>
                </div>
                <div class="p-6">
                    <!-- Language Tabs -->
                    <div class="flex gap-2 mb-4">
                        <button
                            v-for="lang in languages"
                            :key="lang.id"
                            @click="selectedLanguage = lang.id"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="selectedLanguage === lang.id ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            {{ lang.name }}
                        </button>
                    </div>

                    <!-- Code Block -->
                    <div class="bg-gray-900 rounded-lg overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-2 bg-gray-800">
                            <span class="text-xs text-gray-400">{{ getLanguageTitle(selectedLanguage) }}</span>
                            <button
                                @click="copyCode(codeExamples[selectedLanguage])"
                                class="text-xs text-gray-400 hover:text-white flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Copier
                            </button>
                        </div>
                        <pre class="p-4 text-sm text-gray-300 font-mono overflow-x-auto"><code>{{ codeExamples[selectedLanguage] }}</code></pre>
                    </div>
                </div>
            </div>

            <!-- Webhook Events -->
            <div class="bg-white rounded-xl border mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="text-xl">📡</span>
                        Evenements Webhook
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-4">
                        Configurez des webhooks pour recevoir des notifications en temps reel lorsque des evenements se produisent.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="event in webhookEvents"
                            :key="event.name"
                            class="p-4 border rounded-lg hover:bg-gray-50"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">{{ event.icon }}</span>
                                <code class="text-sm font-mono text-primary-600">{{ event.name }}</code>
                            </div>
                            <p class="text-sm text-gray-600">{{ event.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate Limiting -->
            <div class="bg-white rounded-xl border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="text-xl">⏱️</span>
                        Limites de taux (Rate Limiting)
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-6 bg-blue-50 rounded-xl">
                            <p class="text-3xl font-bold text-blue-600 mb-2">60</p>
                            <p class="text-sm text-gray-600">requetes/minute</p>
                            <p class="text-xs text-gray-500 mt-1">Endpoints authentifies</p>
                        </div>
                        <div class="text-center p-6 bg-green-50 rounded-xl">
                            <p class="text-3xl font-bold text-green-600 mb-2">10</p>
                            <p class="text-sm text-gray-600">requetes/minute</p>
                            <p class="text-xs text-gray-500 mt-1">Endpoints publics</p>
                        </div>
                        <div class="text-center p-6 bg-purple-50 rounded-xl">
                            <p class="text-3xl font-bold text-purple-600 mb-2">1000</p>
                            <p class="text-sm text-gray-600">webhooks/heure</p>
                            <p class="text-xs text-gray-500 mt-1">Par tenant</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-2">Headers de reponse</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <code class="bg-gray-200 px-2 py-0.5 rounded">X-RateLimit-Limit</code>
                                <span class="text-gray-600">Nombre max de requetes</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <code class="bg-gray-200 px-2 py-0.5 rounded">X-RateLimit-Remaining</code>
                                <span class="text-gray-600">Requetes restantes</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <code class="bg-gray-200 px-2 py-0.5 rounded">X-RateLimit-Reset</code>
                                <span class="text-gray-600">Timestamp de reinitialisation</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const expandedCategories = ref(['Authentification']);
const selectedLanguage = ref('curl');

const languages = [
    { id: 'curl', name: 'cURL' },
    { id: 'php', name: 'PHP' },
    { id: 'javascript', name: 'JavaScript' },
    { id: 'python', name: 'Python' },
];

const apiEndpoints = {
    'Authentification': [
        { method: 'POST', path: '/api/v1/auth/login', description: 'Authentification et obtention du token' },
        { method: 'POST', path: '/api/v1/auth/logout', description: 'Deconnexion et invalidation du token' },
        { method: 'GET', path: '/api/v1/auth/user', description: 'Informations de l\'utilisateur connecte' },
    ],
    'Boxes': [
        { method: 'GET', path: '/api/v1/boxes', description: 'Liste des boxes avec filtres' },
        { method: 'GET', path: '/api/v1/boxes/{id}', description: 'Details d\'un box' },
        { method: 'GET', path: '/api/v1/boxes/{id}/availability', description: 'Disponibilite d\'un box' },
        { method: 'PUT', path: '/api/v1/boxes/{id}/status', description: 'Modifier le statut d\'un box' },
    ],
    'Clients': [
        { method: 'GET', path: '/api/v1/customers', description: 'Liste des clients' },
        { method: 'POST', path: '/api/v1/customers', description: 'Creer un client' },
        { method: 'GET', path: '/api/v1/customers/{id}', description: 'Details d\'un client' },
        { method: 'PUT', path: '/api/v1/customers/{id}', description: 'Modifier un client' },
    ],
    'Contrats': [
        { method: 'GET', path: '/api/v1/contracts', description: 'Liste des contrats' },
        { method: 'POST', path: '/api/v1/contracts', description: 'Creer un contrat' },
        { method: 'GET', path: '/api/v1/contracts/{id}', description: 'Details d\'un contrat' },
        { method: 'POST', path: '/api/v1/contracts/{id}/terminate', description: 'Resilier un contrat' },
    ],
    'Factures': [
        { method: 'GET', path: '/api/v1/invoices', description: 'Liste des factures' },
        { method: 'POST', path: '/api/v1/invoices', description: 'Creer une facture' },
        { method: 'GET', path: '/api/v1/invoices/{id}', description: 'Details d\'une facture' },
        { method: 'GET', path: '/api/v1/invoices/{id}/pdf', description: 'Telecharger le PDF' },
    ],
    'Reservations': [
        { method: 'GET', path: '/api/v1/bookings', description: 'Liste des reservations' },
        { method: 'POST', path: '/api/v1/bookings', description: 'Creer une reservation' },
        { method: 'GET', path: '/api/v1/bookings/{uuid}', description: 'Details d\'une reservation' },
        { method: 'PATCH', path: '/api/v1/bookings/{uuid}/status', description: 'Modifier le statut' },
    ],
};

const webhookEvents = [
    { name: 'contract.created', icon: '📄', description: 'Un nouveau contrat a ete signe' },
    { name: 'contract.terminated', icon: '📄', description: 'Un contrat a ete resilie' },
    { name: 'invoice.created', icon: '💰', description: 'Une facture a ete generee' },
    { name: 'invoice.paid', icon: '💰', description: 'Une facture a ete payee' },
    { name: 'payment.received', icon: '💳', description: 'Un paiement a ete recu' },
    { name: 'payment.failed', icon: '💳', description: 'Un paiement a echoue' },
    { name: 'customer.created', icon: '👤', description: 'Un nouveau client cree' },
    { name: 'booking.created', icon: '📅', description: 'Nouvelle reservation' },
    { name: 'box.reserved', icon: '📦', description: 'Un box a ete reserve' },
];

const codeExamples = {
    curl: `# Lister les boxes disponibles
curl -X GET "https://votre-domaine.com/api/v1/boxes?status=available" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Accept: application/json"

# Creer un client
curl -X POST "https://votre-domaine.com/api/v1/customers" \\
  -H "Authorization: Bearer YOUR_API_KEY" \\
  -H "Content-Type: application/json" \\
  -d '{
    "first_name": "Jean",
    "last_name": "Dupont",
    "email": "jean.dupont@example.com",
    "phone": "+33612345678"
  }'`,

    php: `<?php
// Installation: composer require guzzlehttp/guzzle

$client = new GuzzleHttp\\Client([
    'base_uri' => 'https://votre-domaine.com/api/v1/',
    'headers' => [
        'Authorization' => 'Bearer YOUR_API_KEY',
        'Accept' => 'application/json',
    ],
]);

// Lister les boxes disponibles
$response = $client->get('boxes', [
    'query' => ['status' => 'available']
]);
$boxes = json_decode($response->getBody(), true);

// Creer un client
$response = $client->post('customers', [
    'json' => [
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'jean.dupont@example.com',
        'phone' => '+33612345678',
    ]
]);
$customer = json_decode($response->getBody(), true);`,

    javascript: `// Avec Fetch API
const API_KEY = 'YOUR_API_KEY';
const BASE_URL = 'https://votre-domaine.com/api/v1';

// Lister les boxes disponibles
const response = await fetch(\`\${BASE_URL}/boxes?status=available\`, {
    headers: {
        'Authorization': \`Bearer \${API_KEY}\`,
        'Accept': 'application/json',
    },
});
const boxes = await response.json();

// Creer un client
const customerResponse = await fetch(\`\${BASE_URL}/customers\`, {
    method: 'POST',
    headers: {
        'Authorization': \`Bearer \${API_KEY}\`,
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        first_name: 'Jean',
        last_name: 'Dupont',
        email: 'jean.dupont@example.com',
        phone: '+33612345678',
    }),
});
const customer = await customerResponse.json();`,

    python: `import requests

API_KEY = 'YOUR_API_KEY'
BASE_URL = 'https://votre-domaine.com/api/v1'

headers = {
    'Authorization': f'Bearer {API_KEY}',
    'Accept': 'application/json',
}

# Lister les boxes disponibles
response = requests.get(
    f'{BASE_URL}/boxes',
    headers=headers,
    params={'status': 'available'}
)
boxes = response.json()

# Creer un client
customer_data = {
    'first_name': 'Jean',
    'last_name': 'Dupont',
    'email': 'jean.dupont@example.com',
    'phone': '+33612345678',
}
response = requests.post(
    f'{BASE_URL}/customers',
    headers=headers,
    json=customer_data
)
customer = response.json()`,
};

function toggleCategory(category) {
    const index = expandedCategories.value.indexOf(category);
    if (index === -1) {
        expandedCategories.value.push(category);
    } else {
        expandedCategories.value.splice(index, 1);
    }
}

function getCategoryIcon(category) {
    const icons = {
        'Authentification': '🔐',
        'Boxes': '📦',
        'Clients': '👥',
        'Contrats': '📄',
        'Factures': '💰',
        'Reservations': '📅',
    };
    return icons[category] || '📋';
}

function getMethodClass(method) {
    const classes = {
        'GET': 'bg-green-100 text-green-700',
        'POST': 'bg-blue-100 text-blue-700',
        'PUT': 'bg-amber-100 text-amber-700',
        'PATCH': 'bg-purple-100 text-purple-700',
        'DELETE': 'bg-red-100 text-red-700',
    };
    return classes[method] || 'bg-gray-100 text-gray-700';
}

function getLanguageTitle(lang) {
    const titles = {
        'curl': 'cURL - Terminal',
        'php': 'PHP - Guzzle',
        'javascript': 'JavaScript - Fetch API',
        'python': 'Python - Requests',
    };
    return titles[lang] || lang;
}

function copyCode(code) {
    navigator.clipboard.writeText(code);
    alert('Code copie dans le presse-papier');
}
</script>

<style scoped>
.bg-primary-600 {
    background-color: #8FBD56;
}
.bg-primary-700 {
    background-color: #7aa74a;
}
.text-primary-600 {
    color: #8FBD56;
}
</style>
