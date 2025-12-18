<template>
    <transition name="slide-down">
        <div
            v-if="showBanner && !isDismissed"
            class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-4 py-3 shadow-lg"
        >
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-sm">Activez les notifications</p>
                        <p class="text-xs text-white/80">Restez informe des paiements, contrats et alertes</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="handleEnable"
                        :disabled="loading"
                        class="px-4 py-2 bg-white text-primary-600 text-sm font-medium rounded-lg hover:bg-white/90 transition-colors disabled:opacity-50"
                    >
                        <span v-if="loading">Activation...</span>
                        <span v-else>Activer</span>
                    </button>
                    <button
                        @click="dismiss"
                        class="p-2 text-white/70 hover:text-white transition-colors"
                        title="Plus tard"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePushNotifications } from '@/Composables/usePushNotifications';

const {
    isSupported,
    isSubscribed,
    permission,
    loading,
    canSubscribe,
    isDenied,
    initialize,
    subscribe,
} = usePushNotifications();

const isDismissed = ref(false);
const dismissedUntil = ref(null);

// Check if banner should show
const showBanner = computed(() => {
    // Don't show if not supported
    if (!isSupported.value) return false;

    // Don't show if already subscribed
    if (isSubscribed.value) return false;

    // Don't show if permission denied
    if (isDenied.value) return false;

    // Don't show if dismissed recently
    if (dismissedUntil.value && new Date() < new Date(dismissedUntil.value)) {
        return false;
    }

    return true;
});

// Handle enable click
const handleEnable = async () => {
    const success = await subscribe();
    if (success) {
        isDismissed.value = true;
    }
};

// Dismiss banner for a while
const dismiss = () => {
    isDismissed.value = true;
    // Don't show again for 7 days
    const dismissDate = new Date();
    dismissDate.setDate(dismissDate.getDate() + 7);
    dismissedUntil.value = dismissDate.toISOString();
    localStorage.setItem('push_banner_dismissed', dismissedUntil.value);
};

// Check if previously dismissed
onMounted(async () => {
    await initialize();

    const stored = localStorage.getItem('push_banner_dismissed');
    if (stored) {
        dismissedUntil.value = stored;
        if (new Date() < new Date(stored)) {
            isDismissed.value = true;
        }
    }
});
</script>

<style scoped>
.bg-primary-500 {
    background-color: #8FBD56;
}
.bg-primary-600 {
    background-color: #7aa74a;
}
.text-primary-600 {
    color: #7aa74a;
}

.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
    transform: translateY(-100%);
    opacity: 0;
}
</style>
