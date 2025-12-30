<template>
    <TenantLayout title="Preferences de notifications">
        <div class="max-w-3xl mx-auto p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="text-3xl">🔔</span>
                    Preferences de notifications
                </h1>
                <p class="text-gray-600 mt-1">
                    Gerez vos notifications et alertes
                </p>
            </div>

            <!-- Push Notifications Section -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">Notifications Push</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Recevez des alertes en temps reel sur votre appareil</p>
                </div>

                <div class="p-6">
                    <!-- Status Banner -->
                    <div
                        class="mb-6 p-4 rounded-lg"
                        :class="statusBannerClass"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ statusIcon }}</span>
                            <div>
                                <p class="font-medium">{{ statusTitle }}</p>
                                <p class="text-sm opacity-80">{{ statusDescription }}</p>
                            </div>
                        </div>

                        <div v-if="canSubscribe" class="mt-4">
                            <button
                                @click="handleSubscribe"
                                :disabled="loading"
                                class="px-4 py-2 bg-white text-primary-600 rounded-lg font-medium hover:bg-white/90 transition-colors disabled:opacity-50"
                            >
                                {{ loading ? 'Activation...' : 'Activer les notifications' }}
                            </button>
                        </div>

                        <div v-else-if="isSubscribed" class="mt-4">
                            <button
                                @click="handleUnsubscribe"
                                :disabled="loading"
                                class="px-4 py-2 bg-white/20 text-white rounded-lg font-medium hover:bg-white/30 transition-colors disabled:opacity-50"
                            >
                                {{ loading ? 'Desactivation...' : 'Desactiver' }}
                            </button>
                            <button
                                @click="handleTestNotification"
                                :disabled="loading"
                                class="ml-2 px-4 py-2 bg-white text-primary-600 rounded-lg font-medium hover:bg-white/90 transition-colors disabled:opacity-50"
                            >
                                Tester
                            </button>
                        </div>
                    </div>

                    <!-- Connected Devices -->
                    <div v-if="subscriptions.length > 0" class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Appareils connectes</h4>
                        <div class="space-y-2">
                            <div
                                v-for="sub in subscriptions"
                                :key="sub.id"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">{{ getDeviceIcon(sub.device_type) }}</span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ sub.browser }} sur {{ sub.os }}</p>
                                        <p class="text-xs text-gray-500">
                                            Derniere utilisation: {{ sub.last_used_at || 'Jamais' }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="revokeDevice(sub.id)"
                                    class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                                    title="Deconnecter cet appareil"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Types -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Types de notifications</h4>
                        <div class="space-y-3">
                            <label
                                v-for="notifType in notificationTypes"
                                :key="notifType.key"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">{{ notifType.icon }}</span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ notifType.label }}</p>
                                        <p class="text-xs text-gray-500">{{ notifType.description }}</p>
                                    </div>
                                </div>
                                <input
                                    type="checkbox"
                                    v-model="preferences[notifType.key]"
                                    @change="savePreferences"
                                    class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Notifications Section -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">Notifications par email</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Recapitulatifs et alertes par email</p>
                </div>

                <div class="p-6">
                    <div class="space-y-3">
                        <label
                            v-for="emailType in emailTypes"
                            :key="emailType.key"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ emailType.icon }}</span>
                                <div>
                                    <p class="font-medium text-gray-900">{{ emailType.label }}</p>
                                    <p class="text-xs text-gray-500">{{ emailType.description }}</p>
                                </div>
                            </div>
                            <input
                                type="checkbox"
                                v-model="emailPreferences[emailType.key]"
                                @change="saveEmailPreferences"
                                class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                            />
                        </label>
                    </div>
                </div>
            </div>

            <!-- Quiet Hours -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">Heures de silence</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Ne pas envoyer de notifications pendant ces heures</p>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-medium text-gray-900">Activer les heures de silence</span>
                        <button
                            @click="quietHoursEnabled = !quietHoursEnabled; saveQuietHours()"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="quietHoursEnabled ? 'bg-primary' : 'bg-gray-200'"
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="quietHoursEnabled ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>

                    <div v-if="quietHoursEnabled" class="flex items-center gap-4">
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">De</label>
                            <input
                                type="time"
                                v-model="quietHoursStart"
                                @change="saveQuietHours"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">A</label>
                            <input
                                type="time"
                                v-model="quietHoursEnd"
                                @change="saveQuietHours"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { usePushNotifications } from '@/composables/usePushNotifications';

const {
    isSupported,
    isSubscribed,
    permission,
    loading,
    canSubscribe,
    isDenied,
    initialize,
    subscribe,
    unsubscribe,
    sendTest,
} = usePushNotifications();

const subscriptions = ref([]);
const preferences = ref({
    payment_reminders: true,
    contract_alerts: true,
    booking_notifications: true,
    iot_alerts: true,
    marketing: false,
});

const emailPreferences = ref({
    daily_summary: true,
    weekly_report: true,
    payment_alerts: true,
    contract_reminders: true,
    marketing_emails: false,
});

const quietHoursEnabled = ref(false);
const quietHoursStart = ref('22:00');
const quietHoursEnd = ref('08:00');

// Notification types configuration
const notificationTypes = [
    {
        key: 'payment_reminders',
        icon: '💳',
        label: 'Rappels de paiement',
        description: 'Factures en attente et retards de paiement',
    },
    {
        key: 'contract_alerts',
        icon: '📄',
        label: 'Alertes contrats',
        description: 'Contrats expirant et nouvelles signatures',
    },
    {
        key: 'booking_notifications',
        icon: '📅',
        label: 'Nouvelles reservations',
        description: 'Reservations en ligne et demandes',
    },
    {
        key: 'iot_alerts',
        icon: '🌡️',
        label: 'Alertes capteurs IoT',
        description: 'Temperature, humidite et intrusions',
    },
    {
        key: 'marketing',
        icon: '📢',
        label: 'Actualites et conseils',
        description: 'Nouveautes et bonnes pratiques',
    },
];

const emailTypes = [
    {
        key: 'daily_summary',
        icon: '📊',
        label: 'Resume quotidien',
        description: 'Activite de la journee chaque soir',
    },
    {
        key: 'weekly_report',
        icon: '📈',
        label: 'Rapport hebdomadaire',
        description: 'Performance de la semaine chaque lundi',
    },
    {
        key: 'payment_alerts',
        icon: '💰',
        label: 'Alertes paiement',
        description: 'Paiements recus et impayes',
    },
    {
        key: 'contract_reminders',
        icon: '📝',
        label: 'Rappels contrats',
        description: 'Contrats a renouveler',
    },
    {
        key: 'marketing_emails',
        icon: '📰',
        label: 'Newsletter',
        description: 'Actualites et offres speciales',
    },
];

// Status computed properties
const statusBannerClass = computed(() => {
    if (!isSupported.value) return 'bg-gray-500 text-white';
    if (isDenied.value) return 'bg-red-500 text-white';
    if (isSubscribed.value) return 'bg-green-500 text-white';
    return 'bg-blue-500 text-white';
});

const statusIcon = computed(() => {
    if (!isSupported.value) return '🚫';
    if (isDenied.value) return '⛔';
    if (isSubscribed.value) return '✅';
    return '🔔';
});

const statusTitle = computed(() => {
    if (!isSupported.value) return 'Non supporte';
    if (isDenied.value) return 'Notifications bloquees';
    if (isSubscribed.value) return 'Notifications activees';
    return 'Notifications desactivees';
});

const statusDescription = computed(() => {
    if (!isSupported.value) return 'Votre navigateur ne supporte pas les notifications push';
    if (isDenied.value) return 'Vous avez bloque les notifications. Modifiez les parametres de votre navigateur.';
    if (isSubscribed.value) return 'Vous recevrez des alertes en temps reel sur cet appareil';
    return 'Activez les notifications pour rester informe';
});

// Methods
const handleSubscribe = async () => {
    const success = await subscribe();
    if (success) {
        await loadSubscriptions();
    }
};

const handleUnsubscribe = async () => {
    const success = await unsubscribe();
    if (success) {
        await loadSubscriptions();
    }
};

const handleTestNotification = async () => {
    await sendTest();
};

const loadSubscriptions = async () => {
    try {
        const response = await fetch('/api/v1/push/subscriptions');
        const data = await response.json();
        subscriptions.value = data.subscriptions || [];
    } catch (e) {
        console.error('Error loading subscriptions:', e);
    }
};

const revokeDevice = async (subscriptionId) => {
    if (!confirm('Deconnecter cet appareil ?')) return;

    try {
        await fetch(`/api/v1/push/subscriptions/${subscriptionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        await loadSubscriptions();
    } catch (e) {
        console.error('Error revoking device:', e);
    }
};

const savePreferences = async () => {
    try {
        await fetch('/api/v1/push/preferences', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(preferences.value),
        });
    } catch (e) {
        console.error('Error saving preferences:', e);
    }
};

const saveEmailPreferences = async () => {
    // Save email preferences
    localStorage.setItem('email_preferences', JSON.stringify(emailPreferences.value));
};

const saveQuietHours = () => {
    localStorage.setItem('quiet_hours', JSON.stringify({
        enabled: quietHoursEnabled.value,
        start: quietHoursStart.value,
        end: quietHoursEnd.value,
    }));
};

const getDeviceIcon = (type) => {
    const icons = {
        web: '💻',
        mobile: '📱',
        tablet: '📱',
    };
    return icons[type] || '💻';
};

// Load saved settings
onMounted(async () => {
    await initialize();
    await loadSubscriptions();

    // Load quiet hours
    const savedQuietHours = localStorage.getItem('quiet_hours');
    if (savedQuietHours) {
        const data = JSON.parse(savedQuietHours);
        quietHoursEnabled.value = data.enabled;
        quietHoursStart.value = data.start;
        quietHoursEnd.value = data.end;
    }

    // Load email preferences
    const savedEmailPrefs = localStorage.getItem('email_preferences');
    if (savedEmailPrefs) {
        emailPreferences.value = { ...emailPreferences.value, ...JSON.parse(savedEmailPrefs) };
    }
});
</script>

<style scoped>
.bg-primary {
    background-color: #8FBD56;
}
.text-primary-600 {
    color: #7aa74a;
}
</style>
