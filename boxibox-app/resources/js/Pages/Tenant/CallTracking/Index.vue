<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { PhoneIcon, PhoneArrowDownLeftIcon, PhoneXMarkIcon, ClockIcon, CurrencyEuroIcon, ChartBarIcon, Cog6ToothIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    statistics: Object,
    statsBySource: Array,
    trackingNumbers: Array,
    missedCalls: Array,
    callbacksRequired: Array,
    sourceOptions: Object,
    dateRange: Object,
});

const scheduleCallback = (callId, datetime) => {
    router.post(route('tenant.call-tracking.schedule-callback', callId), {
        callback_at: datetime,
    });
};

const completeCallback = (callId) => {
    router.post(route('tenant.call-tracking.complete-callback', callId));
};

const getSourceLabel = (source) => {
    return props.sourceOptions?.[source] || source;
};

const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};
</script>

<template>
    <TenantLayout title="Call Tracking">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Call Tracking</h1>
                    <p class="text-gray-600 dark:text-gray-400">Suivez vos appels et mesurez les conversions</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('tenant.call-tracking.calls')" class="btn-secondary">
                        <PhoneIcon class="w-5 h-5 mr-2" />
                        Historique
                    </Link>
                    <Link :href="route('tenant.call-tracking.numbers')" class="btn-secondary">
                        <PhoneArrowDownLeftIcon class="w-5 h-5 mr-2" />
                        Numéros
                    </Link>
                    <Link :href="route('tenant.call-tracking.settings')" class="btn-primary">
                        <Cog6ToothIcon class="w-5 h-5 mr-2" />
                        Paramètres
                    </Link>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <PhoneIcon class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.total_calls || 0 }}</p>
                            <p class="text-sm text-gray-500">Total appels</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <PhoneArrowDownLeftIcon class="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.answered_calls || 0 }}</p>
                            <p class="text-sm text-gray-500">Répondus</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                            <PhoneXMarkIcon class="w-5 h-5 text-red-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.missed_calls || 0 }}</p>
                            <p class="text-sm text-gray-500">Manqués</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <ClockIcon class="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatDuration(statistics?.avg_duration) }}</p>
                            <p class="text-sm text-gray-500">Durée moy.</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                            <ChartBarIcon class="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.converted_calls || 0 }}</p>
                            <p class="text-sm text-gray-500">Convertis</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                            <CurrencyEuroIcon class="w-5 h-5 text-indigo-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics?.conversion_value || 0).toLocaleString() }}€</p>
                            <p class="text-sm text-gray-500">Valeur</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Missed Calls & Callbacks Required -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Missed Calls Today -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <PhoneXMarkIcon class="w-5 h-5 text-red-500" />
                            Appels manqués aujourd'hui
                        </h2>
                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">
                            {{ missedCalls?.length || 0 }}
                        </span>
                    </div>
                    <div v-if="missedCalls?.length" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto">
                        <div v-for="call in missedCalls" :key="call.id" class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ call.caller_number }}</p>
                                <p class="text-sm text-gray-500">{{ call.caller_name || 'Inconnu' }} · {{ new Date(call.started_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                    {{ getSourceLabel(call.source) }}
                                </span>
                                <Link :href="route('tenant.call-tracking.calls.show', call.id)" class="btn-sm btn-secondary">
                                    Voir
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center text-gray-500">
                        <PhoneArrowDownLeftIcon class="w-10 h-10 text-green-300 mx-auto mb-2" />
                        Aucun appel manqué aujourd'hui
                    </div>
                </div>

                <!-- Callbacks Required -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <ArrowPathIcon class="w-5 h-5 text-orange-500" />
                            Rappels à effectuer
                        </h2>
                        <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2 py-1 rounded-full">
                            {{ callbacksRequired?.length || 0 }}
                        </span>
                    </div>
                    <div v-if="callbacksRequired?.length" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto">
                        <div v-for="call in callbacksRequired" :key="call.id" class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ call.caller_number }}</p>
                                <p class="text-sm text-gray-500">
                                    Rappel: {{ new Date(call.callback_scheduled_at).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) }}
                                </p>
                            </div>
                            <button @click="completeCallback(call.id)" class="btn-sm btn-success">
                                Effectué
                            </button>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center text-gray-500">
                        Aucun rappel en attente
                    </div>
                </div>
            </div>

            <!-- Stats by Source -->
            <div v-if="statsBySource?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Performance par source</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Source</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Appels</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Répondus</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durée moy.</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Convertis</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Valeur</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="stat in statsBySource" :key="stat.source">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ getSourceLabel(stat.source) }}</td>
                                <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ stat.total_calls }}</td>
                                <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ stat.answered_calls }}</td>
                                <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ formatDuration(stat.avg_duration) }}</td>
                                <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ stat.converted_calls }}</td>
                                <td class="px-6 py-4 text-right text-green-600">{{ (stat.conversion_value || 0).toLocaleString() }}€</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tracking Numbers -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Numéros de tracking actifs</h2>
                    <Link :href="route('tenant.call-tracking.numbers')" class="text-blue-600 hover:underline text-sm">
                        Gérer
                    </Link>
                </div>
                <div v-if="trackingNumbers?.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="number in trackingNumbers" :key="number.id" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <PhoneIcon class="w-5 h-5 text-blue-600" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ number.phone_number }}</p>
                                <p class="text-sm text-gray-500">{{ number.friendly_name || getSourceLabel(number.source) }} → {{ number.forward_to }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                number.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ]">
                                {{ number.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <span class="text-sm text-gray-500">{{ number.calls_count || 0 }} appels</span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-6 py-12 text-center">
                    <PhoneIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucun numéro de tracking configuré</p>
                    <Link :href="route('tenant.call-tracking.numbers')" class="text-blue-600 hover:underline mt-2 inline-block">
                        Ajouter un numéro
                    </Link>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.btn-sm { @apply px-3 py-1.5 text-sm rounded-lg font-medium transition inline-flex items-center; }
.btn-primary { @apply bg-blue-600 text-white hover:bg-blue-700; }
.btn-secondary { @apply bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300; }
.btn-success { @apply bg-green-600 text-white hover:bg-green-700; }
</style>
