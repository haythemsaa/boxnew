<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="close"></div>

        <!-- Sidebar Panel -->
        <div class="absolute inset-y-0 right-0 w-96 bg-white shadow-xl transform transition-transform duration-300">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Personnaliser le tableau de bord</h2>
                    <p class="text-sm text-gray-500">Configurez vos widgets</p>
                </div>
                <button
                    @click="close"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-4 overflow-y-auto h-full pb-32">
                <!-- Edit Mode Toggle -->
                <div class="mb-6 p-4 bg-blue-50 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-blue-900">Mode edition</h3>
                            <p class="text-xs text-blue-700 mt-0.5">Glissez-deposez pour reorganiser</p>
                        </div>
                        <button
                            @click="toggleEditMode"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="isEditMode ? 'bg-blue-600' : 'bg-gray-200'"
                        >
                            <span
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="isEditMode ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>
                </div>

                <!-- Widgets List -->
                <div class="space-y-3">
                    <h3 class="text-sm font-medium text-gray-700 uppercase tracking-wider">Widgets disponibles</h3>

                    <div
                        v-for="widget in widgets"
                        :key="widget.id"
                        class="bg-white border rounded-xl p-4 transition-all"
                        :class="widget.enabled ? 'border-primary-200 bg-primary-50/30' : 'border-gray-200'"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ getWidgetIcon(widget.id) }}</span>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ widget.name }}</h4>
                                    <p class="text-xs text-gray-500">{{ getWidgetDescription(widget.id) }}</p>
                                </div>
                            </div>
                            <button
                                @click="toggleWidget(widget.id)"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="widget.enabled ? 'bg-primary' : 'bg-gray-200'"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="widget.enabled ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>
                        </div>

                        <!-- Size Selector (only if enabled) -->
                        <div v-if="widget.enabled" class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Taille:</span>
                            <div class="flex gap-1">
                                <button
                                    v-for="size in ['small', 'medium', 'large']"
                                    :key="size"
                                    @click="setWidgetSize(widget.id, size)"
                                    class="px-2 py-1 text-xs rounded transition-colors"
                                    :class="widget.size === size ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                >
                                    {{ getSizeLabel(size) }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layout Presets -->
                <div class="mt-6 space-y-3">
                    <h3 class="text-sm font-medium text-gray-700 uppercase tracking-wider">Presets de disposition</h3>

                    <div class="grid grid-cols-2 gap-3">
                        <button
                            @click="applyPreset('default')"
                            class="p-4 border border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50/30 transition-all text-left"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📊</span>
                                <span class="font-medium text-gray-900">Standard</span>
                            </div>
                            <p class="text-xs text-gray-500">Configuration equilibree</p>
                        </button>

                        <button
                            @click="applyPreset('compact')"
                            class="p-4 border border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50/30 transition-all text-left"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📱</span>
                                <span class="font-medium text-gray-900">Compact</span>
                            </div>
                            <p class="text-xs text-gray-500">Vue minimale</p>
                        </button>

                        <button
                            @click="applyPreset('analytics')"
                            class="p-4 border border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50/30 transition-all text-left"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📈</span>
                                <span class="font-medium text-gray-900">Analytics</span>
                            </div>
                            <p class="text-xs text-gray-500">Focus sur les metriques</p>
                        </button>

                        <button
                            @click="applyPreset('operations')"
                            class="p-4 border border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50/30 transition-all text-left"
                        >
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">🔧</span>
                                <span class="font-medium text-gray-900">Operations</span>
                            </div>
                            <p class="text-xs text-gray-500">Gestion quotidienne</p>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="absolute bottom-0 left-0 right-0 p-4 bg-white border-t">
                <div class="flex items-center justify-between">
                    <button
                        @click="resetToDefault"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Reinitialiser
                    </button>
                    <button
                        @click="close"
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors"
                    >
                        Terminer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useDashboardWidgets } from '@/composables/useDashboardWidgets';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const {
    widgets,
    isEditMode,
    toggleWidget,
    setWidgetSize,
    resetToDefault,
    toggleEditMode,
} = useDashboardWidgets();

const close = () => {
    emit('close');
};

const getWidgetIcon = (id) => {
    const icons = {
        revenue: '💰',
        occupation: '📦',
        alerts: '🔔',
        'quick-actions': '⚡',
        'ai-insights': '🤖',
        'recent-activity': '📋',
        'expiring-contracts': '⏰',
        'recent-payments': '💳',
        'overdue-invoices': '⚠️',
    };
    return icons[id] || '📊';
};

const getWidgetDescription = (id) => {
    const descriptions = {
        revenue: 'Revenus et tendances financieres',
        occupation: 'Taux d\'occupation des boxes',
        alerts: 'Alertes et notifications urgentes',
        'quick-actions': 'Raccourcis vers les actions frequentes',
        'ai-insights': 'Predictions et recommandations IA',
        'recent-activity': 'Historique des dernieres actions',
        'expiring-contracts': 'Contrats arrivant a echeance',
        'recent-payments': 'Derniers paiements recus',
        'overdue-invoices': 'Factures en retard de paiement',
    };
    return descriptions[id] || '';
};

const getSizeLabel = (size) => {
    const labels = {
        small: 'S',
        medium: 'M',
        large: 'L',
    };
    return labels[size] || size;
};

const applyPreset = (preset) => {
    const presets = {
        default: () => {
            widgets.value.forEach(w => {
                w.enabled = true;
                w.size = 'medium';
            });
        },
        compact: () => {
            widgets.value.forEach(w => {
                w.enabled = ['revenue', 'occupation', 'alerts', 'quick-actions'].includes(w.id);
                w.size = 'small';
            });
        },
        analytics: () => {
            widgets.value.forEach(w => {
                w.enabled = ['revenue', 'occupation', 'ai-insights', 'recent-activity'].includes(w.id);
                w.size = w.id === 'revenue' ? 'large' : 'medium';
            });
        },
        operations: () => {
            widgets.value.forEach(w => {
                w.enabled = ['alerts', 'quick-actions', 'expiring-contracts', 'overdue-invoices', 'recent-activity'].includes(w.id);
                w.size = 'medium';
            });
        },
    };

    if (presets[preset]) {
        presets[preset]();
    }
};
</script>

<style scoped>
.bg-primary {
    background-color: #8FBD56;
}
.bg-primary-dark {
    background-color: #7aa74a;
}
.border-primary-200 {
    border-color: rgba(143, 189, 86, 0.3);
}
.border-primary-300 {
    border-color: rgba(143, 189, 86, 0.5);
}
.bg-primary-50\/30 {
    background-color: rgba(143, 189, 86, 0.05);
}
</style>
