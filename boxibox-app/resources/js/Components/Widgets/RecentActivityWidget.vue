<template>
    <DashboardWidget
        :title="title"
        icon="📋"
        :loading="loading"
        :refreshable="true"
        :collapsible="true"
        @refresh="refresh"
        content-class="p-0"
    >
        <div class="divide-y divide-gray-100">
            <div
                v-for="activity in activities.slice(0, maxItems)"
                :key="activity.id"
                class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
                @click="handleClick(activity)"
            >
                <div class="flex items-start gap-3">
                    <!-- Activity Icon -->
                    <div
                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                        :class="getActivityBgClass(activity.type)"
                    >
                        <span class="text-sm">{{ getActivityIcon(activity.type) }}</span>
                    </div>

                    <!-- Activity Content -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900">
                            <span class="font-medium">{{ activity.user }}</span>
                            <span class="text-gray-500"> {{ activity.action }}</span>
                            <span class="font-medium text-gray-700"> {{ activity.target }}</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ formatTime(activity.created_at) }}</p>
                    </div>

                    <!-- Amount if applicable -->
                    <div v-if="activity.amount" class="flex-shrink-0 text-right">
                        <span
                            class="text-sm font-semibold"
                            :class="activity.amount > 0 ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ activity.amount > 0 ? '+' : '' }}{{ formatCurrency(activity.amount) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="activities.length === 0" class="p-8 text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-xl">📭</span>
                </div>
                <p class="text-sm font-medium text-gray-900">Aucune activite recente</p>
                <p class="text-xs text-gray-500 mt-1">Les activites apparaitront ici</p>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ activities.length }} activites</span>
                <Link href="/tenant/activity" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    Voir tout l'historique →
                </Link>
            </div>
        </template>
    </DashboardWidget>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import DashboardWidget from '@/Components/DashboardWidget.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Activite recente',
    },
    activities: {
        type: Array,
        default: () => [],
    },
    maxItems: {
        type: Number,
        default: 5,
    },
});

const emit = defineEmits(['refresh']);

const loading = ref(false);

const getActivityIcon = (type) => {
    const icons = {
        contract_created: '📄',
        contract_signed: '✍️',
        contract_terminated: '❌',
        payment_received: '💰',
        payment_failed: '💳',
        invoice_created: '🧾',
        invoice_sent: '📧',
        customer_created: '👤',
        customer_updated: '✏️',
        box_reserved: '📦',
        box_released: '🔓',
        reminder_sent: '🔔',
        lead_converted: '🎯',
    };
    return icons[type] || '📌';
};

const getActivityBgClass = (type) => {
    const classes = {
        contract_created: 'bg-blue-100',
        contract_signed: 'bg-green-100',
        contract_terminated: 'bg-red-100',
        payment_received: 'bg-emerald-100',
        payment_failed: 'bg-red-100',
        invoice_created: 'bg-indigo-100',
        invoice_sent: 'bg-purple-100',
        customer_created: 'bg-cyan-100',
        customer_updated: 'bg-gray-100',
        box_reserved: 'bg-amber-100',
        box_released: 'bg-gray-100',
        reminder_sent: 'bg-orange-100',
        lead_converted: 'bg-pink-100',
    };
    return classes[type] || 'bg-gray-100';
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "A l'instant";
    if (diffMins < 60) return `Il y a ${diffMins}min`;
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    if (diffDays < 7) return `Il y a ${diffDays}j`;
    return date.toLocaleDateString('fr-FR');
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Math.abs(value || 0));
};

const handleClick = (activity) => {
    if (activity.link) {
        router.visit(activity.link);
    }
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
