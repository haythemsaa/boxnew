<template>
    <DashboardWidget
        :title="title"
        icon="🔔"
        :loading="loading"
        :refreshable="true"
        :collapsible="true"
        @refresh="refresh"
        content-class="p-0"
    >
        <div class="divide-y divide-gray-100">
            <!-- Critical Alerts -->
            <div
                v-for="alert in sortedAlerts.slice(0, maxAlerts)"
                :key="alert.id"
                class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
                @click="handleAlertClick(alert)"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                        :class="getAlertIconClass(alert.type)"
                    >
                        <span class="text-sm">{{ getAlertIcon(alert.type) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ alert.title }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ alert.message }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ formatTime(alert.created_at) }}</p>
                    </div>
                    <div v-if="alert.action" class="flex-shrink-0">
                        <button
                            class="text-xs px-2 py-1 rounded-full font-medium transition-colors"
                            :class="getActionButtonClass(alert.type)"
                            @click.stop="handleAction(alert)"
                        >
                            {{ alert.action }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="alerts.length === 0" class="p-8 text-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-xl">✓</span>
                </div>
                <p class="text-sm font-medium text-gray-900">Tout est en ordre !</p>
                <p class="text-xs text-gray-500 mt-1">Aucune alerte a traiter</p>
            </div>

            <!-- View All -->
            <div v-if="alerts.length > maxAlerts" class="p-3 bg-gray-50 text-center">
                <button
                    @click="showAll = true"
                    class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                >
                    Voir {{ alerts.length - maxAlerts }} alertes supplementaires
                </button>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ alerts.length }} alertes actives</span>
                <Link href="/tenant/notifications" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Toutes les notifications →
                </Link>
            </div>
        </template>
    </DashboardWidget>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardWidget from '@/Components/DashboardWidget.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Alertes',
    },
    alerts: {
        type: Array,
        default: () => [],
    },
    maxAlerts: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(['refresh', 'action']);

const loading = ref(false);
const showAll = ref(false);

const sortedAlerts = computed(() => {
    const priorityOrder = { critical: 0, warning: 1, info: 2 };
    return [...props.alerts].sort((a, b) => {
        const priorityDiff = (priorityOrder[a.type] || 3) - (priorityOrder[b.type] || 3);
        if (priorityDiff !== 0) return priorityDiff;
        return new Date(b.created_at) - new Date(a.created_at);
    });
});

const getAlertIcon = (type) => {
    const icons = {
        critical: '🚨',
        warning: '⚠️',
        info: 'ℹ️',
        success: '✅',
        payment: '💳',
        contract: '📄',
        expiry: '⏰',
    };
    return icons[type] || '📌';
};

const getAlertIconClass = (type) => {
    const classes = {
        critical: 'bg-red-100',
        warning: 'bg-amber-100',
        info: 'bg-blue-100',
        success: 'bg-green-100',
        payment: 'bg-purple-100',
        contract: 'bg-indigo-100',
        expiry: 'bg-orange-100',
    };
    return classes[type] || 'bg-gray-100';
};

const getActionButtonClass = (type) => {
    const classes = {
        critical: 'bg-red-100 text-red-700 hover:bg-red-200',
        warning: 'bg-amber-100 text-amber-700 hover:bg-amber-200',
        info: 'bg-blue-100 text-blue-700 hover:bg-blue-200',
    };
    return classes[type] || 'bg-gray-100 text-gray-700 hover:bg-gray-200';
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) return `Il y a ${diffMins}min`;
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    if (diffDays < 7) return `Il y a ${diffDays}j`;
    return date.toLocaleDateString('fr-FR');
};

const handleAlertClick = (alert) => {
    if (alert.link) {
        router.visit(alert.link);
    }
};

const handleAction = (alert) => {
    emit('action', alert);
};

const refresh = async () => {
    loading.value = true;
    emit('refresh');
    setTimeout(() => {
        loading.value = false;
    }, 1000);
};
</script>

<style scoped>
.text-primary-600 {
    color: #8FBD56;
}
.text-primary-700 {
    color: #7aa74a;
}
</style>
