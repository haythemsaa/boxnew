// Dashboard Widgets Index
// Export all dashboard widget components

export { default as RevenueWidget } from './RevenueWidget.vue';
export { default as OccupationWidget } from './OccupationWidget.vue';
export { default as AlertsWidget } from './AlertsWidget.vue';
export { default as QuickActionsWidget } from './QuickActionsWidget.vue';
export { default as AIInsightsWidget } from './AIInsightsWidget.vue';
export { default as RecentActivityWidget } from './RecentActivityWidget.vue';

// Widget configuration
export const WIDGET_TYPES = {
    REVENUE: 'revenue',
    OCCUPATION: 'occupation',
    ALERTS: 'alerts',
    QUICK_ACTIONS: 'quick-actions',
    AI_INSIGHTS: 'ai-insights',
    RECENT_ACTIVITY: 'recent-activity',
    EXPIRING_CONTRACTS: 'expiring-contracts',
    RECENT_PAYMENTS: 'recent-payments',
    OVERDUE_INVOICES: 'overdue-invoices',
};

// Widget metadata for customization
export const WIDGET_METADATA = {
    [WIDGET_TYPES.REVENUE]: {
        name: 'Revenus',
        icon: '💰',
        description: 'Revenus et tendances financieres',
        component: 'RevenueWidget',
        defaultSize: 'medium',
    },
    [WIDGET_TYPES.OCCUPATION]: {
        name: 'Occupation',
        icon: '📦',
        description: "Taux d'occupation des boxes",
        component: 'OccupationWidget',
        defaultSize: 'medium',
    },
    [WIDGET_TYPES.ALERTS]: {
        name: 'Alertes',
        icon: '🔔',
        description: 'Alertes et notifications urgentes',
        component: 'AlertsWidget',
        defaultSize: 'medium',
    },
    [WIDGET_TYPES.QUICK_ACTIONS]: {
        name: 'Actions rapides',
        icon: '⚡',
        description: 'Raccourcis vers les actions frequentes',
        component: 'QuickActionsWidget',
        defaultSize: 'small',
    },
    [WIDGET_TYPES.AI_INSIGHTS]: {
        name: 'Insights IA',
        icon: '🤖',
        description: 'Predictions et recommandations IA',
        component: 'AIInsightsWidget',
        defaultSize: 'medium',
    },
    [WIDGET_TYPES.RECENT_ACTIVITY]: {
        name: 'Activite recente',
        icon: '📋',
        description: 'Historique des dernieres actions',
        component: 'RecentActivityWidget',
        defaultSize: 'medium',
    },
    [WIDGET_TYPES.EXPIRING_CONTRACTS]: {
        name: 'Contrats expirant',
        icon: '⏰',
        description: 'Contrats arrivant a echeance',
        component: null, // Built-in widget
        defaultSize: 'large',
    },
    [WIDGET_TYPES.RECENT_PAYMENTS]: {
        name: 'Paiements recents',
        icon: '💳',
        description: 'Derniers paiements recus',
        component: null, // Built-in widget
        defaultSize: 'large',
    },
    [WIDGET_TYPES.OVERDUE_INVOICES]: {
        name: 'Factures en retard',
        icon: '⚠️',
        description: 'Factures en retard de paiement',
        component: null, // Built-in widget
        defaultSize: 'large',
    },
};
