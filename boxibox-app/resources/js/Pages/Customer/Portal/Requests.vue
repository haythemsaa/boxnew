<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes Demandes</h1>
                    <p class="text-gray-600 dark:text-gray-400">Suivez l'avancement de vos demandes</p>
                </div>
                <Link
                    :href="route('customer.portal.support.index')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    <i class="fas fa-plus"></i>
                    Nouvelle demande
                </Link>
            </div>

            <!-- Requests List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="requests.data?.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="request in requests.data"
                        :key="request.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div :class="getTypeIconClass(request.type)" class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i :class="getTypeIcon(request.type)" class="text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ request.subject }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ request.description }}
                                    </p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span>
                                            <i class="fas fa-tag mr-1"></i>
                                            {{ getTypeLabel(request.type) }}
                                        </span>
                                        <span>
                                            <i class="fas fa-calendar mr-1"></i>
                                            {{ formatDate(request.created_at) }}
                                        </span>
                                        <span v-if="request.contract">
                                            <i class="fas fa-file-contract mr-1"></i>
                                            {{ request.contract.contract_number }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span :class="getStatusClass(request.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(request.status) }}
                                </span>
                                <span :class="getPriorityClass(request.priority)" class="text-xs">
                                    <i class="fas fa-flag mr-1"></i>
                                    {{ getPriorityLabel(request.priority) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 text-center">
                    <i class="fas fa-ticket-alt text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucune demande</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Vous n'avez pas encore fait de demande.</p>
                    <Link
                        :href="route('customer.portal.support.index')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                        <i class="fas fa-plus"></i>
                        Creer une demande
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="requests.links?.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2">
                    <Link
                        v-for="link in requests.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1 rounded-lg text-sm',
                            link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                            !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-500 hover:text-white'
                        ]"
                    />
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    requests: Object,
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getTypeIcon = (type) => {
    const icons = {
        'maintenance': 'fas fa-tools text-orange-600',
        'box_change': 'fas fa-exchange-alt text-blue-600',
        'termination': 'fas fa-door-open text-red-600',
        'general': 'fas fa-comment-dots text-purple-600',
    };
    return icons[type] || 'fas fa-question text-gray-600';
};

const getTypeIconClass = (type) => {
    const classes = {
        'maintenance': 'bg-orange-100 dark:bg-orange-900/50',
        'box_change': 'bg-blue-100 dark:bg-blue-900/50',
        'termination': 'bg-red-100 dark:bg-red-900/50',
        'general': 'bg-purple-100 dark:bg-purple-900/50',
    };
    return classes[type] || 'bg-gray-100 dark:bg-gray-700';
};

const getTypeLabel = (type) => {
    const labels = {
        'maintenance': 'Maintenance',
        'box_change': 'Changement de box',
        'termination': 'Resiliation',
        'general': 'Question generale',
    };
    return labels[type] || type;
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        'in_progress': 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        'resolved': 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        'closed': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'En attente',
        'in_progress': 'En cours',
        'resolved': 'Resolu',
        'closed': 'Ferme',
    };
    return labels[status] || status;
};

const getPriorityClass = (priority) => {
    const classes = {
        'low': 'text-gray-500',
        'medium': 'text-yellow-600',
        'high': 'text-red-600',
    };
    return classes[priority] || 'text-gray-500';
};

const getPriorityLabel = (priority) => {
    const labels = {
        'low': 'Basse',
        'medium': 'Moyenne',
        'high': 'Haute',
    };
    return labels[priority] || priority;
};
</script>
