import { ref, computed, watch } from 'vue';

// Default widget configuration
const defaultWidgets = [
    { id: 'revenue', name: 'Revenus', enabled: true, order: 1, size: 'medium', column: 1 },
    { id: 'occupation', name: 'Occupation', enabled: true, order: 2, size: 'medium', column: 2 },
    { id: 'alerts', name: 'Alertes', enabled: true, order: 3, size: 'medium', column: 3 },
    { id: 'quick-actions', name: 'Actions rapides', enabled: true, order: 4, size: 'small', column: 1 },
    { id: 'ai-insights', name: 'Insights IA', enabled: true, order: 5, size: 'medium', column: 2 },
    { id: 'recent-activity', name: 'Activite recente', enabled: true, order: 6, size: 'medium', column: 3 },
    { id: 'expiring-contracts', name: 'Contrats expirant', enabled: true, order: 7, size: 'large', column: 1 },
    { id: 'recent-payments', name: 'Paiements recents', enabled: true, order: 8, size: 'large', column: 2 },
    { id: 'overdue-invoices', name: 'Factures en retard', enabled: true, order: 9, size: 'large', column: 3 },
];

// Available widget sizes
const widgetSizes = {
    small: { cols: 1, rows: 1 },
    medium: { cols: 1, rows: 2 },
    large: { cols: 2, rows: 2 },
    full: { cols: 3, rows: 2 },
};

export function useDashboardWidgets() {
    // Load saved configuration from localStorage
    const loadConfig = () => {
        try {
            const saved = localStorage.getItem('dashboard_widgets');
            if (saved) {
                const parsed = JSON.parse(saved);
                // Merge with defaults to handle new widgets
                return defaultWidgets.map(defaultWidget => {
                    const savedWidget = parsed.find(w => w.id === defaultWidget.id);
                    return savedWidget ? { ...defaultWidget, ...savedWidget } : defaultWidget;
                });
            }
        } catch (e) {
            console.error('Failed to load dashboard config:', e);
        }
        return [...defaultWidgets];
    };

    const widgets = ref(loadConfig());
    const isEditMode = ref(false);
    const draggedWidget = ref(null);

    // Save configuration to localStorage
    const saveConfig = () => {
        try {
            localStorage.setItem('dashboard_widgets', JSON.stringify(widgets.value));
        } catch (e) {
            console.error('Failed to save dashboard config:', e);
        }
    };

    // Watch for changes and save
    watch(widgets, saveConfig, { deep: true });

    // Computed: enabled widgets sorted by order
    const enabledWidgets = computed(() => {
        return widgets.value
            .filter(w => w.enabled)
            .sort((a, b) => a.order - b.order);
    });

    // Computed: widgets by column
    const widgetsByColumn = computed(() => {
        const columns = { 1: [], 2: [], 3: [] };
        enabledWidgets.value.forEach(widget => {
            const col = widget.column || 1;
            if (columns[col]) {
                columns[col].push(widget);
            }
        });
        return columns;
    });

    // Toggle widget visibility
    const toggleWidget = (widgetId) => {
        const widget = widgets.value.find(w => w.id === widgetId);
        if (widget) {
            widget.enabled = !widget.enabled;
        }
    };

    // Change widget size
    const setWidgetSize = (widgetId, size) => {
        const widget = widgets.value.find(w => w.id === widgetId);
        if (widget && widgetSizes[size]) {
            widget.size = size;
        }
    };

    // Move widget to a new position
    const moveWidget = (widgetId, newOrder, newColumn) => {
        const widget = widgets.value.find(w => w.id === widgetId);
        if (widget) {
            widget.order = newOrder;
            if (newColumn) {
                widget.column = newColumn;
            }
            // Reorder other widgets
            reorderWidgets();
        }
    };

    // Reorder widgets to ensure sequential order
    const reorderWidgets = () => {
        const enabled = widgets.value.filter(w => w.enabled).sort((a, b) => a.order - b.order);
        enabled.forEach((widget, index) => {
            widget.order = index + 1;
        });
    };

    // Reset to default configuration
    const resetToDefault = () => {
        widgets.value = [...defaultWidgets];
    };

    // Drag and drop handlers
    const startDrag = (widget, event) => {
        if (!isEditMode.value) return;
        draggedWidget.value = widget;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', widget.id);
    };

    const onDragOver = (event) => {
        if (!isEditMode.value) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
    };

    const onDrop = (targetWidget, event) => {
        if (!isEditMode.value || !draggedWidget.value) return;
        event.preventDefault();

        const draggedOrder = draggedWidget.value.order;
        const targetOrder = targetWidget.order;
        const draggedColumn = draggedWidget.value.column;
        const targetColumn = targetWidget.column;

        // Swap positions
        draggedWidget.value.order = targetOrder;
        draggedWidget.value.column = targetColumn;
        targetWidget.order = draggedOrder;
        targetWidget.column = draggedColumn;

        draggedWidget.value = null;
        reorderWidgets();
    };

    const endDrag = () => {
        draggedWidget.value = null;
    };

    // Toggle edit mode
    const toggleEditMode = () => {
        isEditMode.value = !isEditMode.value;
    };

    // Get widget by ID
    const getWidget = (widgetId) => {
        return widgets.value.find(w => w.id === widgetId);
    };

    // Get widget size class for grid
    const getWidgetSizeClass = (widget) => {
        const size = widget.size || 'medium';
        const sizeConfig = widgetSizes[size];
        return {
            'col-span-1': sizeConfig.cols === 1,
            'col-span-2': sizeConfig.cols === 2,
            'col-span-3': sizeConfig.cols === 3,
            'row-span-1': sizeConfig.rows === 1,
            'row-span-2': sizeConfig.rows === 2,
        };
    };

    return {
        widgets,
        enabledWidgets,
        widgetsByColumn,
        isEditMode,
        draggedWidget,
        widgetSizes,
        toggleWidget,
        setWidgetSize,
        moveWidget,
        resetToDefault,
        startDrag,
        onDragOver,
        onDrop,
        endDrag,
        toggleEditMode,
        getWidget,
        getWidgetSizeClass,
    };
}
