<template>
    <DashboardWidget
        :title="title"
        icon="⚡"
        :refreshable="false"
        :collapsible="true"
        content-class="p-3"
    >
        <div class="grid grid-cols-2 gap-2">
            <Link
                v-for="action in actions"
                :key="action.name"
                :href="action.href"
                class="group flex flex-col items-center p-3 rounded-xl border border-gray-100 hover:border-primary-200 hover:bg-primary-50/50 transition-all duration-200"
            >
                <div
                    class="w-10 h-10 rounded-xl flex items-center justify-center mb-2 transition-transform group-hover:scale-110"
                    :class="action.bgColor"
                >
                    <span class="text-lg">{{ action.icon }}</span>
                </div>
                <span class="text-xs font-medium text-gray-700 group-hover:text-primary-700 text-center">
                    {{ action.name }}
                </span>
            </Link>
        </div>

        <!-- Custom Actions Slot -->
        <div v-if="showCustomize" class="mt-4 pt-4 border-t border-gray-100">
            <button
                @click="$emit('customize')"
                class="w-full flex items-center justify-center gap-2 p-2 text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Personnaliser les raccourcis
            </button>
        </div>
    </DashboardWidget>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import DashboardWidget from '@/Components/DashboardWidget.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Actions rapides',
    },
    actions: {
        type: Array,
        default: () => [
            {
                name: 'Nouveau client',
                href: '/tenant/customers/create',
                icon: '👤',
                bgColor: 'bg-blue-100',
            },
            {
                name: 'Nouveau contrat',
                href: '/tenant/contracts/create',
                icon: '📄',
                bgColor: 'bg-green-100',
            },
            {
                name: 'Facturation',
                href: '/tenant/bulk-invoicing',
                icon: '💳',
                bgColor: 'bg-purple-100',
            },
            {
                name: 'Plan boxes',
                href: '/tenant/boxes/plan',
                icon: '🗺️',
                bgColor: 'bg-amber-100',
            },
            {
                name: 'Relances',
                href: '/tenant/reminders',
                icon: '📧',
                bgColor: 'bg-red-100',
            },
            {
                name: 'Signatures',
                href: '/tenant/signatures',
                icon: '✍️',
                bgColor: 'bg-indigo-100',
            },
        ],
    },
    showCustomize: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['customize']);
</script>

<style scoped>
.bg-primary-50\/50 {
    background-color: rgba(143, 189, 86, 0.05);
}
.hover\:border-primary-200:hover {
    border-color: rgba(143, 189, 86, 0.3);
}
.group-hover\:text-primary-700 {
    color: #7aa74a;
}
</style>
