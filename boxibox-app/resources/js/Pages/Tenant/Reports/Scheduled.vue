<template>
    <TenantLayout title="Rapports Programmés">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="text-3xl">📊</span>
                        Rapports Programmés
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Automatisez l'envoi de vos rapports par email
                    </p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouveau rapport programmé
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Rapports actifs</p>
                            <p class="text-2xl font-bold text-gray-900">{{ activeCount }}</p>
                        </div>
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">✅</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Envoyés ce mois</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.sent_this_month }}</p>
                        </div>
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">📤</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Prochain envoi</p>
                            <p class="text-lg font-bold text-gray-900">{{ nextSendDate }}</p>
                        </div>
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">⏰</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Destinataires</p>
                            <p class="text-2xl font-bold text-gray-900">{{ totalRecipients }}</p>
                        </div>
                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                            <span class="text-xl">👥</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scheduled Reports List -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">Vos rapports programmés</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="report in scheduledReports"
                        :key="report.id"
                        class="p-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <!-- Status Indicator -->
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center"
                                    :class="report.is_active ? 'bg-green-100' : 'bg-gray-100'"
                                >
                                    <span :class="report.is_active ? 'text-green-600' : 'text-gray-400'" class="text-xl">
                                        {{ getReportTypeIcon(report.custom_report?.type || 'custom') }}
                                    </span>
                                </div>

                                <div>
                                    <h4 class="font-medium text-gray-900 flex items-center gap-2">
                                        {{ report.name }}
                                        <span
                                            class="px-2 py-0.5 text-xs rounded-full"
                                            :class="report.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        >
                                            {{ report.is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </h4>
                                    <p class="text-sm text-gray-500 mt-0.5">
                                        {{ report.custom_report?.name || 'Rapport personnalisé' }}
                                    </p>

                                    <!-- Schedule Info -->
                                    <div class="flex flex-wrap gap-3 mt-2">
                                        <span class="flex items-center gap-1 text-xs text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ getFrequencyLabel(report.frequency) }}
                                        </span>
                                        <span class="flex items-center gap-1 text-xs text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            {{ (report.recipients || []).length }} destinataire(s)
                                        </span>
                                        <span class="flex items-center gap-1 text-xs text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Format {{ report.format?.toUpperCase() || 'CSV' }}
                                        </span>
                                    </div>

                                    <!-- Last/Next Send Info -->
                                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                                        <span v-if="report.last_sent_at">
                                            Dernier envoi: {{ formatDate(report.last_sent_at) }}
                                        </span>
                                        <span v-if="report.next_send_at" class="text-primary-600 font-medium">
                                            Prochain: {{ formatDate(report.next_send_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="sendNow(report)"
                                    :disabled="sending === report.id"
                                    class="px-3 py-1.5 text-sm border rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                                >
                                    {{ sending === report.id ? 'Envoi...' : 'Envoyer' }}
                                </button>
                                <button
                                    @click="toggleActive(report)"
                                    class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                                    :class="report.is_active ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200'"
                                >
                                    {{ report.is_active ? 'Désactiver' : 'Activer' }}
                                </button>
                                <button
                                    @click="editReport(report)"
                                    class="px-3 py-1.5 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    Modifier
                                </button>
                                <button
                                    @click="deleteReport(report)"
                                    class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="scheduledReports.length === 0" class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">📅</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun rapport programmé</h3>
                        <p class="text-gray-500 mb-4">
                            Automatisez l'envoi de vos rapports pour gagner du temps.
                        </p>
                        <button
                            @click="showCreateModal = true"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                        >
                            Créer un rapport programmé
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent History -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden mt-6">
                <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Historique des envois</h3>
                    <Link
                        href="/tenant/reports"
                        class="text-sm text-primary-600 hover:text-primary-700"
                    >
                        Voir tous les rapports →
                    </Link>
                </div>

                <div class="divide-y divide-gray-100">
                    <div
                        v-for="history in recentHistory"
                        :key="history.id"
                        class="p-4 flex items-center justify-between hover:bg-gray-50"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center"
                                :class="history.status === 'completed' ? 'bg-green-100' : 'bg-red-100'"
                            >
                                <span v-if="history.status === 'completed'" class="text-green-600">✓</span>
                                <span v-else class="text-red-600">✗</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ history.scheduled_report?.name || history.custom_report?.name || 'Rapport' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ formatDate(history.created_at) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span>{{ history.row_count }} lignes</span>
                            <span>{{ history.generation_time_ms }}ms</span>
                            <span class="px-2 py-0.5 rounded text-xs font-medium uppercase bg-gray-100">
                                {{ history.format }}
                            </span>
                        </div>
                    </div>

                    <div v-if="recentHistory.length === 0" class="p-8 text-center text-gray-500">
                        Aucun historique d'envoi
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <Teleport to="body">
                <div v-if="showCreateModal || editingReport" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="closeModal"></div>
                    <div class="relative bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div class="p-6 border-b sticky top-0 bg-white">
                            <h3 class="text-xl font-bold text-gray-900">
                                {{ editingReport ? 'Modifier le rapport programmé' : 'Nouveau rapport programmé' }}
                            </h3>
                        </div>

                        <form @submit.prevent="saveReport" class="p-6 space-y-6">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom du rapport programmé *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Ex: Rapport mensuel d'occupation"
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>

                            <!-- Report Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Type de rapport *
                                </label>
                                <select
                                    v-model="form.report_type"
                                    required
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option value="">Sélectionner un type</option>
                                    <option value="rent_roll">Rent Roll</option>
                                    <option value="occupancy">Occupation</option>
                                    <option value="revenue">Revenus</option>
                                    <option value="aging">Balance âgée</option>
                                    <option value="cash_flow">Cash Flow</option>
                                </select>
                            </div>

                            <!-- Frequency -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fréquence d'envoi *
                                </label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label
                                        v-for="freq in frequencies"
                                        :key="freq.value"
                                        class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50"
                                        :class="{ 'border-primary-500 bg-primary-50': form.frequency === freq.value }"
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.frequency"
                                            :value="freq.value"
                                            class="text-primary-600"
                                        />
                                        <div>
                                            <span class="font-medium">{{ freq.label }}</span>
                                            <p class="text-xs text-gray-500">{{ freq.description }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Day Selection -->
                            <div v-if="form.frequency === 'weekly'" class="grid grid-cols-7 gap-2">
                                <button
                                    v-for="(day, index) in weekDays"
                                    :key="index"
                                    type="button"
                                    @click="form.day_of_week = index"
                                    class="px-3 py-2 text-sm border rounded-lg transition-colors"
                                    :class="form.day_of_week === index ? 'bg-primary-600 text-white border-primary-600' : 'hover:bg-gray-50'"
                                >
                                    {{ day }}
                                </button>
                            </div>

                            <div v-if="form.frequency === 'monthly'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Jour du mois
                                </label>
                                <select
                                    v-model="form.day_of_month"
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option v-for="day in 28" :key="day" :value="day">{{ day }}</option>
                                </select>
                            </div>

                            <!-- Format -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Format d'export
                                </label>
                                <div class="flex gap-3">
                                    <label
                                        v-for="format in formats"
                                        :key="format.value"
                                        class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer hover:bg-gray-50"
                                        :class="{ 'border-primary-500 bg-primary-50': form.format === format.value }"
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.format"
                                            :value="format.value"
                                            class="text-primary-600"
                                        />
                                        <span>{{ format.label }}</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Recipients -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Destinataires *
                                </label>
                                <div class="space-y-2">
                                    <div v-for="(email, index) in form.recipients" :key="index" class="flex gap-2">
                                        <input
                                            v-model="form.recipients[index]"
                                            type="email"
                                            placeholder="email@example.com"
                                            class="flex-1 px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                        />
                                        <button
                                            v-if="form.recipients.length > 1"
                                            type="button"
                                            @click="removeRecipient(index)"
                                            class="px-3 py-2 text-red-500 hover:bg-red-50 rounded-lg"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="addRecipient"
                                    class="mt-2 text-sm text-primary-600 hover:text-primary-700 font-medium"
                                >
                                    + Ajouter un destinataire
                                </button>
                            </div>

                            <!-- Submit -->
                            <div class="flex justify-end gap-3 pt-4 border-t">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-6 py-2.5 border rounded-lg font-medium hover:bg-gray-50"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="saving"
                                    class="px-6 py-2.5 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 disabled:opacity-50"
                                >
                                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    scheduledReports: {
        type: Array,
        default: () => [],
    },
    recentHistory: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ sent_this_month: 0 }),
    },
});

const showCreateModal = ref(false);
const editingReport = ref(null);
const saving = ref(false);
const sending = ref(null);

const form = reactive({
    name: '',
    report_type: '',
    frequency: 'monthly',
    day_of_week: 1,
    day_of_month: 1,
    format: 'csv',
    recipients: [''],
});

const frequencies = [
    { value: 'daily', label: 'Quotidien', description: 'Tous les jours à 8h' },
    { value: 'weekly', label: 'Hebdomadaire', description: 'Chaque semaine' },
    { value: 'monthly', label: 'Mensuel', description: 'Le même jour chaque mois' },
];

const formats = [
    { value: 'csv', label: 'CSV' },
    { value: 'xlsx', label: 'Excel' },
    { value: 'pdf', label: 'PDF' },
];

const weekDays = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

const activeCount = computed(() => props.scheduledReports.filter(r => r.is_active).length);

const totalRecipients = computed(() => {
    return props.scheduledReports.reduce((sum, r) => sum + (r.recipients?.length || 0), 0);
});

const nextSendDate = computed(() => {
    const active = props.scheduledReports.filter(r => r.is_active && r.next_send_at);
    if (active.length === 0) return '-';
    const next = active.sort((a, b) => new Date(a.next_send_at) - new Date(b.next_send_at))[0];
    return formatDate(next.next_send_at);
});

function getReportTypeIcon(type) {
    const icons = {
        rent_roll: '📋',
        occupancy: '📊',
        revenue: '💰',
        aging: '⏰',
        cash_flow: '💵',
        custom: '📄',
    };
    return icons[type] || '📄';
}

function getFrequencyLabel(freq) {
    const labels = {
        daily: 'Quotidien',
        weekly: 'Hebdomadaire',
        monthly: 'Mensuel',
    };
    return labels[freq] || freq;
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function addRecipient() {
    form.recipients.push('');
}

function removeRecipient(index) {
    form.recipients.splice(index, 1);
}

function editReport(report) {
    editingReport.value = report;
    form.name = report.name;
    form.report_type = report.custom_report?.type || '';
    form.frequency = report.frequency;
    form.day_of_week = report.day_of_week || 1;
    form.day_of_month = report.day_of_month || 1;
    form.format = report.format || 'csv';
    form.recipients = report.recipients?.length ? [...report.recipients] : [''];
}

function closeModal() {
    showCreateModal.value = false;
    editingReport.value = null;
    form.name = '';
    form.report_type = '';
    form.frequency = 'monthly';
    form.day_of_week = 1;
    form.day_of_month = 1;
    form.format = 'csv';
    form.recipients = [''];
}

function saveReport() {
    saving.value = true;
    const data = {
        ...form,
        recipients: form.recipients.filter(r => r.trim() !== ''),
    };

    if (editingReport.value) {
        router.put(`/tenant/reports/scheduled/${editingReport.value.id}`, data, {
            onFinish: () => {
                saving.value = false;
                closeModal();
            },
        });
    } else {
        router.post('/tenant/reports/scheduled', data, {
            onFinish: () => {
                saving.value = false;
                closeModal();
            },
        });
    }
}

async function sendNow(report) {
    sending.value = report.id;
    try {
        const response = await fetch(`/tenant/reports/scheduled/${report.id}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        const result = await response.json();
        if (result.success) {
            alert('Rapport envoyé avec succès !');
            router.reload();
        } else {
            alert('Erreur: ' + (result.error || 'Échec de l\'envoi'));
        }
    } catch (e) {
        alert('Erreur lors de l\'envoi');
    } finally {
        sending.value = null;
    }
}

function toggleActive(report) {
    router.put(`/tenant/reports/scheduled/${report.id}`, {
        ...report,
        is_active: !report.is_active,
    }, {
        preserveScroll: true,
    });
}

function deleteReport(report) {
    if (confirm('Supprimer ce rapport programmé ?')) {
        router.delete(`/tenant/reports/scheduled/${report.id}`);
    }
}
</script>

<style scoped>
.bg-primary-600 {
    background-color: #8FBD56;
}
.bg-primary-700 {
    background-color: #7aa74a;
}
.text-primary-600 {
    color: #8FBD56;
}
.text-primary-700 {
    color: #7aa74a;
}
.bg-primary-50 {
    background-color: rgba(143, 189, 86, 0.1);
}
.border-primary-500 {
    border-color: #8FBD56;
}
</style>
