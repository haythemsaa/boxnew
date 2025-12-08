<script setup>
/**
 * IoT Reports Page - Insurance and environmental reports
 */
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    DocumentTextIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    CalendarIcon,
    ChevronLeftIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    reports: Object,
    sites: Array,
})

// Form for generating new report
const showGenerateModal = ref(false)
const form = useForm({
    site_id: '',
    period_start: '',
    period_end: '',
})

// Generate report
const generateReport = () => {
    form.post('/tenant/iot/reports/generate', {
        preserveScroll: true,
        onSuccess: () => {
            showGenerateModal.value = false
            form.reset()
        },
    })
}

// Helpers
const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const getStatusBadge = (status) => {
    switch (status) {
        case 'ready': return 'bg-green-100 text-green-700'
        case 'pending': return 'bg-amber-100 text-amber-700'
        case 'error': return 'bg-red-100 text-red-700'
        default: return 'bg-gray-100 text-gray-700'
    }
}

const getStatusIcon = (status) => {
    switch (status) {
        case 'ready': return CheckCircleIcon
        case 'pending': return ClockIcon
        case 'error': return ExclamationTriangleIcon
        default: return DocumentTextIcon
    }
}
</script>

<template>
    <TenantLayout>
        <Head title="Rapports IoT" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Rapports IoT</h1>
                    <p class="text-gray-500 mt-1">
                        Rapports environnementaux pour les assurances
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        href="/tenant/iot"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors"
                    >
                        <ChevronLeftIcon class="h-5 w-5" />
                        Retour
                    </Link>
                    <button
                        @click="showGenerateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Générer un rapport
                    </button>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <DocumentTextIcon class="h-6 w-6 text-blue-600 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-blue-900">Rapports pour l'assurance</h3>
                        <p class="text-blue-700 text-sm mt-1">
                            Ces rapports attestent des conditions environnementales de vos installations.
                            Ils incluent les relevés de température, humidité, et tout incident survenu
                            pendant la période sélectionnée.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reports List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6">
                    <div v-if="reports?.data?.length" class="space-y-4">
                        <div
                            v-for="report in reports.data"
                            :key="report.id"
                            class="flex items-center gap-4 p-4 border border-gray-200 rounded-xl hover:shadow-md transition-shadow"
                        >
                            <div :class="['w-12 h-12 rounded-lg flex items-center justify-center', report.status === 'ready' ? 'bg-green-100' : 'bg-gray-100']">
                                <component
                                    :is="getStatusIcon(report.status)"
                                    :class="['h-6 w-6', report.status === 'ready' ? 'text-green-600' : 'text-gray-500']"
                                />
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-semibold text-gray-900">
                                        {{ report.site?.name || 'Site' }}
                                    </h4>
                                    <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', getStatusBadge(report.status)]">
                                        {{ report.status === 'ready' ? 'Prêt' : report.status === 'pending' ? 'En cours' : 'Erreur' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">
                                    Période: {{ formatDate(report.period_start) }} - {{ formatDate(report.period_end) }}
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                    <span class="flex items-center gap-1">
                                        <ChartBarIcon class="h-4 w-4" />
                                        Uptime: {{ report.uptime_percentage }}%
                                    </span>
                                    <span v-if="report.total_alerts">
                                        {{ report.total_alerts }} alertes
                                        <span v-if="report.critical_alerts" class="text-red-600">
                                            ({{ report.critical_alerts }} critiques)
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-400">
                                    {{ formatDate(report.created_at) }}
                                </span>
                                <a
                                    v-if="report.status === 'ready'"
                                    :href="`/tenant/iot/reports/${report.id}/download`"
                                    class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-colors"
                                    title="Télécharger le PDF"
                                >
                                    <ArrowDownTrayIcon class="h-5 w-5" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <DocumentTextIcon class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                        <p class="text-gray-500">Aucun rapport généré</p>
                        <p class="text-sm text-gray-400 mb-4">
                            Générez votre premier rapport pour attester des conditions environnementales
                        </p>
                        <button
                            @click="showGenerateModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Générer un rapport
                        </button>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="reports?.data?.length" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        {{ reports.total }} rapport(s)
                    </p>
                </div>
            </div>
        </div>

        <!-- Generate Report Modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Générer un rapport</h3>

                <form @submit.prevent="generateReport" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                        <select
                            v-model="form.site_id"
                            required
                            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Sélectionner un site</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.site_id" class="text-red-500 text-sm mt-1">{{ form.errors.site_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                        <input
                            type="date"
                            v-model="form.period_start"
                            required
                            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.period_start" class="text-red-500 text-sm mt-1">{{ form.errors.period_start }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                        <input
                            type="date"
                            v-model="form.period_end"
                            required
                            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.period_end" class="text-red-500 text-sm mt-1">{{ form.errors.period_end }}</p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            @click="showGenerateModal = false"
                            class="flex-1 px-4 py-2 border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Génération...' : 'Générer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
