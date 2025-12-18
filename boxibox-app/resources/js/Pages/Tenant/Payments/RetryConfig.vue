<script setup>
import { ref, reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    config: Object,
})

const form = reactive({
    max_retries: props.config?.max_retries ?? 4,
    retry_intervals: props.config?.retry_intervals ?? [1, 3, 7, 14],
    retry_times: props.config?.retry_times ?? ['09:00', '14:00', '18:00'],
    use_smart_timing: props.config?.use_smart_timing ?? true,
    avoid_weekends: props.config?.avoid_weekends ?? true,
    avoid_holidays: props.config?.avoid_holidays ?? true,
    notify_customer_before: props.config?.notify_customer_before ?? true,
    notify_hours_before: props.config?.notify_hours_before ?? 24,
    notify_customer_after_failure: props.config?.notify_customer_after_failure ?? true,
    notify_customer_after_success: props.config?.notify_customer_after_success ?? true,
    notify_admin_after_all_failures: props.config?.notify_admin_after_all_failures ?? true,
    allow_card_update: props.config?.allow_card_update ?? true,
    card_update_link_expiry_hours: props.config?.card_update_link_expiry_hours ?? 72,
    final_failure_action: props.config?.final_failure_action ?? 'suspend',
    grace_period_days: props.config?.grace_period_days ?? 7,
    escalation_messages: props.config?.escalation_messages ?? getDefaultMessages(),
})

const saving = ref(false)
const activeTab = ref('general')

function getDefaultMessages() {
    return {
        1: { subject: 'Échec de paiement - Action requise', body: 'Nous n\'avons pas pu débiter votre carte pour votre facture. Nous réessaierons automatiquement.' },
        2: { subject: 'Deuxième tentative de paiement échouée', body: 'Une nouvelle tentative de paiement a échoué. Veuillez vérifier votre moyen de paiement.' },
        3: { subject: 'Urgent - Mise à jour de paiement requise', body: 'Plusieurs tentatives de paiement ont échoué. Mettez à jour votre carte pour éviter la suspension.' },
        4: { subject: 'Dernière tentative - Risque de suspension', body: 'C\'est notre dernière tentative. Sans paiement, votre accès sera suspendu.' },
    }
}

const addRetryInterval = () => {
    const lastInterval = form.retry_intervals[form.retry_intervals.length - 1] || 0
    form.retry_intervals.push(lastInterval + 7)
}

const removeRetryInterval = (index) => {
    if (form.retry_intervals.length > 1) {
        form.retry_intervals.splice(index, 1)
    }
}

const addRetryTime = () => {
    form.retry_times.push('10:00')
}

const removeRetryTime = (index) => {
    if (form.retry_times.length > 1) {
        form.retry_times.splice(index, 1)
    }
}

const saveConfig = () => {
    saving.value = true
    router.post(route('tenant.payment-retries.update-config'), form, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false
        }
    })
}

const tabs = [
    { id: 'general', name: 'Général', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
    { id: 'schedule', name: 'Planning', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'notifications', name: 'Notifications', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
    { id: 'messages', name: 'Messages', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    { id: 'actions', name: 'Actions', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
]
</script>

<template>
    <TenantLayout title="Configuration Retry">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-orange-50/30">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div class="animate-fade-in-up">
                        <div class="flex items-center gap-3 mb-2">
                            <Link
                                :href="route('tenant.payment-retries.index')"
                                class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors"
                            >
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Configuration du Retry</h1>
                                <p class="text-gray-500 mt-1">Personnalisez le comportement des relances automatiques</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 animate-fade-in-up" style="animation-delay: 0.1s">
                    <div class="flex overflow-x-auto border-b border-gray-100">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === tab.id
                                    ? 'border-orange-500 text-orange-600 bg-orange-50/50'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                            </svg>
                            {{ tab.name }}
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- General Tab -->
                        <div v-show="activeTab === 'general'" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre maximum de tentatives</label>
                                <input
                                    v-model.number="form.max_retries"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">Entre 1 et 10 tentatives</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.use_smart_timing"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Timing intelligent</span>
                                        <p class="text-xs text-gray-500">Utilise l'IA pour optimiser les horaires</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.allow_card_update"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Mise à jour de carte</span>
                                        <p class="text-xs text-gray-500">Permettre au client de changer sa carte</p>
                                    </div>
                                </label>
                            </div>

                            <div v-if="form.allow_card_update">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expiration du lien de mise à jour (heures)</label>
                                <input
                                    v-model.number="form.card_update_link_expiry_hours"
                                    type="number"
                                    min="1"
                                    max="168"
                                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                />
                            </div>
                        </div>

                        <!-- Schedule Tab -->
                        <div v-show="activeTab === 'schedule'" class="space-y-6">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-gray-700">Intervalles entre tentatives (jours)</label>
                                    <button
                                        @click="addRetryInterval"
                                        type="button"
                                        class="text-sm text-orange-600 hover:text-orange-700 font-medium"
                                    >
                                        + Ajouter
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div
                                        v-for="(interval, index) in form.retry_intervals"
                                        :key="index"
                                        class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2"
                                    >
                                        <span class="text-xs text-gray-500">J+</span>
                                        <input
                                            v-model.number="form.retry_intervals[index]"
                                            type="number"
                                            min="1"
                                            max="30"
                                            class="w-16 px-2 py-1 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                        />
                                        <button
                                            @click="removeRetryInterval(index)"
                                            type="button"
                                            class="text-gray-400 hover:text-red-500"
                                            :disabled="form.retry_intervals.length <= 1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">Ex: [1, 3, 7, 14] = J+1, J+3, J+7, J+14</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-gray-700">Heures de tentative préférées</label>
                                    <button
                                        @click="addRetryTime"
                                        type="button"
                                        class="text-sm text-orange-600 hover:text-orange-700 font-medium"
                                    >
                                        + Ajouter
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div
                                        v-for="(time, index) in form.retry_times"
                                        :key="index"
                                        class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2"
                                    >
                                        <input
                                            v-model="form.retry_times[index]"
                                            type="time"
                                            class="px-2 py-1 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                        />
                                        <button
                                            @click="removeRetryTime(index)"
                                            type="button"
                                            class="text-gray-400 hover:text-red-500"
                                            :disabled="form.retry_times.length <= 1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.avoid_weekends"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Éviter les week-ends</span>
                                        <p class="text-xs text-gray-500">Pas de tentatives sam/dim</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.avoid_holidays"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Éviter les jours fériés</span>
                                        <p class="text-xs text-gray-500">Jours fériés français</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Notifications Tab -->
                        <div v-show="activeTab === 'notifications'" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.notify_customer_before"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Notifier avant tentative</span>
                                        <p class="text-xs text-gray-500">Prévenir le client avant le prélèvement</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.notify_customer_after_failure"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Notifier après échec</span>
                                        <p class="text-xs text-gray-500">Informer le client d'un échec</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.notify_customer_after_success"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Notifier après succès</span>
                                        <p class="text-xs text-gray-500">Confirmer le paiement récupéré</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.notify_admin_after_all_failures"
                                        type="checkbox"
                                        class="w-5 h-5 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Alerter admin (échec final)</span>
                                        <p class="text-xs text-gray-500">Notifier si toutes les tentatives échouent</p>
                                    </div>
                                </label>
                            </div>

                            <div v-if="form.notify_customer_before">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Délai de notification avant tentative (heures)</label>
                                <input
                                    v-model.number="form.notify_hours_before"
                                    type="number"
                                    min="1"
                                    max="72"
                                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                />
                            </div>
                        </div>

                        <!-- Messages Tab -->
                        <div v-show="activeTab === 'messages'" class="space-y-6">
                            <p class="text-sm text-gray-500 mb-4">Personnalisez les messages d'escalade envoyés à chaque tentative</p>

                            <div
                                v-for="n in form.max_retries"
                                :key="n"
                                class="bg-gray-50 rounded-xl p-4"
                            >
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-sm font-bold">
                                        {{ n }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">Tentative {{ n }}</span>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Objet de l'email</label>
                                        <input
                                            v-model="form.escalation_messages[n].subject"
                                            type="text"
                                            class="block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Corps du message</label>
                                        <textarea
                                            v-model="form.escalation_messages[n].body"
                                            rows="3"
                                            class="block w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Tab -->
                        <div v-show="activeTab === 'actions'" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Action après échec final</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <label
                                        :class="[
                                            'flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                                            form.final_failure_action === 'suspend'
                                                ? 'border-orange-500 bg-orange-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <input
                                            v-model="form.final_failure_action"
                                            type="radio"
                                            value="suspend"
                                            class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                                        />
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Suspendre</span>
                                            <p class="text-xs text-gray-500">Suspendre l'accès client</p>
                                        </div>
                                    </label>

                                    <label
                                        :class="[
                                            'flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                                            form.final_failure_action === 'downgrade'
                                                ? 'border-orange-500 bg-orange-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <input
                                            v-model="form.final_failure_action"
                                            type="radio"
                                            value="downgrade"
                                            class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                                        />
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Rétrograder</span>
                                            <p class="text-xs text-gray-500">Passer en plan gratuit</p>
                                        </div>
                                    </label>

                                    <label
                                        :class="[
                                            'flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                                            form.final_failure_action === 'none'
                                                ? 'border-orange-500 bg-orange-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <input
                                            v-model="form.final_failure_action"
                                            type="radio"
                                            value="none"
                                            class="w-4 h-4 text-orange-600 border-gray-300 focus:ring-orange-500"
                                        />
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Aucune</span>
                                            <p class="text-xs text-gray-500">Action manuelle requise</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.final_failure_action === 'suspend'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Période de grâce avant suspension (jours)</label>
                                <input
                                    v-model.number="form.grace_period_days"
                                    type="number"
                                    min="0"
                                    max="30"
                                    class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">Le client garde l'accès pendant cette période avant la suspension effective</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end animate-fade-in-up" style="animation-delay: 0.2s">
                    <button
                        @click="saveConfig"
                        :disabled="saving"
                        class="inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 transition-all duration-200 shadow-lg shadow-orange-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ saving ? 'Enregistrement...' : 'Enregistrer la configuration' }}
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
