<template>
    <TenantLayout title="Webhooks">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="text-3xl">🔗</span>
                        Webhooks
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Integrez BoxiBox avec vos applications externes
                    </p>
                </div>
                <Link
                    href="/tenant/integrations/webhooks/create"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouveau webhook
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Webhooks actifs</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">✅</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Appels ce mois</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.calls_this_month }}</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">📤</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Taux de succes</p>
                            <p class="text-2xl font-bold" :class="stats.success_rate >= 95 ? 'text-green-600' : stats.success_rate >= 80 ? 'text-amber-600' : 'text-red-600'">
                                {{ stats.success_rate }}%
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">📊</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Echecs recents</p>
                            <p class="text-2xl font-bold" :class="stats.recent_failures > 0 ? 'text-red-600' : 'text-green-600'">
                                {{ stats.recent_failures }}
                            </p>
                        </div>
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">⚠️</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Webhooks List -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Vos webhooks</h3>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher..."
                            class="px-3 py-1.5 border rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="webhook in filteredWebhooks"
                        :key="webhook.id"
                        class="p-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <!-- Status Indicator -->
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center"
                                    :class="webhook.is_active ? 'bg-green-100' : 'bg-gray-100'"
                                >
                                    <span v-if="webhook.is_active" class="text-green-600 text-xl">🔗</span>
                                    <span v-else class="text-gray-400 text-xl">🔒</span>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-900 flex items-center gap-2">
                                        {{ webhook.name }}
                                        <span
                                            class="px-2 py-0.5 text-xs rounded-full"
                                            :class="webhook.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        >
                                            {{ webhook.is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </h4>
                                    <p class="text-sm text-gray-500 mt-0.5 font-mono">{{ webhook.url }}</p>

                                    <!-- Events -->
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        <span
                                            v-for="event in (webhook.events || []).slice(0, 4)"
                                            :key="event"
                                            class="px-2 py-0.5 text-xs bg-blue-50 text-blue-700 rounded"
                                        >
                                            {{ formatEventName(event) }}
                                        </span>
                                        <span
                                            v-if="(webhook.events || []).length > 4"
                                            class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded"
                                        >
                                            +{{ webhook.events.length - 4 }}
                                        </span>
                                    </div>

                                    <!-- Stats -->
                                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                            {{ webhook.successful_calls || 0 }} succes
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                            {{ webhook.failed_calls || 0 }} echecs
                                        </span>
                                        <span v-if="webhook.last_triggered_at">
                                            Dernier appel: {{ formatDate(webhook.last_triggered_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="testWebhook(webhook)"
                                    :disabled="testing === webhook.id"
                                    class="px-3 py-1.5 text-sm border rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                                >
                                    {{ testing === webhook.id ? 'Test...' : 'Tester' }}
                                </button>
                                <Link
                                    :href="`/tenant/integrations/webhooks/${webhook.id}/edit`"
                                    class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    Modifier
                                </Link>
                                <Link
                                    :href="`/tenant/integrations/webhooks/${webhook.id}/logs`"
                                    class="px-3 py-1.5 text-sm text-blue-600 hover:text-blue-700"
                                >
                                    Logs
                                </Link>
                            </div>
                        </div>

                        <!-- Last Error Alert -->
                        <div
                            v-if="webhook.last_status === 'failed' && webhook.last_error"
                            class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg"
                        >
                            <div class="flex items-start gap-2">
                                <span class="text-red-500">⚠️</span>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Derniere erreur</p>
                                    <p class="text-xs text-red-600 mt-0.5">{{ webhook.last_error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="webhooks.length === 0" class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">🔗</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun webhook configure</h3>
                        <p class="text-gray-500 mb-4">
                            Creez votre premier webhook pour integrer BoxiBox avec vos applications.
                        </p>
                        <Link
                            href="/tenant/integrations/webhooks/create"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                        >
                            Creer un webhook
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Documentation Link -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">📚</span>
                    <div>
                        <h4 class="font-medium text-blue-800">Documentation API</h4>
                        <p class="text-sm text-blue-700 mt-1">
                            Consultez notre documentation pour integrer les webhooks BoxiBox dans vos applications.
                        </p>
                        <Link
                            href="/tenant/integrations/api-docs"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium mt-2 inline-block"
                        >
                            Voir la documentation →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    webhooks: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            active: 0,
            calls_this_month: 0,
            success_rate: 100,
            recent_failures: 0,
        }),
    },
});

const search = ref('');
const testing = ref(null);

const filteredWebhooks = computed(() => {
    if (!search.value) return props.webhooks;
    const term = search.value.toLowerCase();
    return props.webhooks.filter(w =>
        w.name.toLowerCase().includes(term) ||
        w.url.toLowerCase().includes(term)
    );
});

const formatEventName = (event) => {
    return event.split('.').pop().replace(/_/g, ' ');
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const testWebhook = async (webhook) => {
    testing.value = webhook.id;

    try {
        const response = await fetch(`/tenant/integrations/webhooks/${webhook.id}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });

        const result = await response.json();

        if (result.success) {
            alert(`Test reussi ! (${result.duration_ms}ms)`);
        } else {
            alert(`Test echoue: ${result.error || 'Erreur inconnue'}`);
        }
    } catch (e) {
        alert('Erreur lors du test');
    } finally {
        testing.value = null;
    }
};
</script>

<style scoped>
.bg-primary-600 {
    background-color: #8FBD56;
}
.bg-primary-700 {
    background-color: #7aa74a;
}
</style>
