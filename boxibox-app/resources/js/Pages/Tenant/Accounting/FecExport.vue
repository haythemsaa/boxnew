<template>
    <TenantLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Export FEC</h1>
                    <p class="text-gray-500 mt-1">Fichier des Ecritures Comptables - Conformite fiscale francaise</p>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start">
                    <InformationCircleIcon class="w-6 h-6 text-blue-500 mt-0.5 mr-3 flex-shrink-0" />
                    <div>
                        <h3 class="font-semibold text-blue-900">Qu'est-ce que le FEC ?</h3>
                        <p class="text-blue-700 text-sm mt-1">
                            Le Fichier des Ecritures Comptables (FEC) est un document obligatoire en France
                            pour toute entreprise soumise a une verification de comptabilite.
                            Il doit etre fourni a l'administration fiscale sur demande.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Generate Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Generer un nouveau FEC</h2>

                <form @submit.prevent="generateFec" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Annee fiscale</label>
                            <select
                                v-model="form.fiscal_year"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option v-for="year in availableYears" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date debut (optionnel)</label>
                            <input
                                type="date"
                                v-model="form.period_start"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date fin (optionnel)</label>
                            <input
                                type="date"
                                v-model="form.period_end"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <DocumentArrowDownIcon v-if="!form.processing" class="w-5 h-5 mr-2" />
                            <svg v-else class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ form.processing ? 'Generation en cours...' : 'Generer le FEC' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export History -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Historique des exports</h2>
                </div>

                <div v-if="exports.data.length === 0" class="p-12 text-center">
                    <DocumentTextIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900">Aucun export FEC</h3>
                    <p class="text-gray-500 mt-1">Generez votre premier fichier FEC ci-dessus</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Annee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ecritures</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equilibre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Genere le</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="fec in exports.data" :key="fec.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-semibold text-gray-900">{{ fec.fiscal_year }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(fec.period_start) }} - {{ formatDate(fec.period_end) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ fec.entries_count }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center" :class="isBalanced(fec) ? 'text-green-600' : 'text-red-600'">
                                        <CheckCircleIcon v-if="isBalanced(fec)" class="w-4 h-4 mr-1" />
                                        <XCircleIcon v-else class="w-4 h-4 mr-1" />
                                        {{ isBalanced(fec) ? 'Equilibre' : 'Desequilibre' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="statusClass(fec.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ statusLabel(fec.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDateTime(fec.generated_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a
                                            v-if="fec.status === 'ready'"
                                            :href="route('tenant.fec.download', fec.id)"
                                            class="text-primary-600 hover:text-primary-900"
                                            title="Telecharger"
                                        >
                                            <ArrowDownTrayIcon class="w-5 h-5" />
                                        </a>
                                        <button
                                            @click="deleteFec(fec)"
                                            class="text-red-600 hover:text-red-900"
                                            title="Supprimer"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="exports.links && exports.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                    <nav class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Affichage de {{ exports.from }} a {{ exports.to }} sur {{ exports.total }}
                        </div>
                        <div class="flex space-x-1">
                            <Link
                                v-for="link in exports.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-1 rounded border text-sm"
                                :class="link.active ? 'bg-primary-50 border-primary-500 text-primary-700' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                            />
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Technical Info -->
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Specifications techniques</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><span class="font-medium">Format:</span> Fichier texte (TXT) avec separateur pipe (|)</li>
                    <li><span class="font-medium">Encodage:</span> UTF-8</li>
                    <li><span class="font-medium">Nom du fichier:</span> SIRENFECANNEEMMJJ.txt</li>
                    <li><span class="font-medium">Conformite:</span> Article A.47 A-1 du Livre des procedures fiscales</li>
                </ul>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    InformationCircleIcon,
    DocumentArrowDownIcon,
    DocumentTextIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    exports: Object,
    availableYears: Array,
    currentYear: Number,
})

const form = useForm({
    fiscal_year: props.currentYear,
    period_start: '',
    period_end: '',
})

const generateFec = () => {
    form.post(route('tenant.fec.generate'), {
        preserveScroll: true,
    })
}

const deleteFec = (fec) => {
    if (confirm('Supprimer cet export FEC ?')) {
        router.delete(route('tenant.fec.destroy', fec.id), {
            preserveScroll: true,
        })
    }
}

const isBalanced = (fec) => {
    return Math.abs(fec.total_debit - fec.total_credit) < 0.01
}

const statusClass = (status) => {
    const classes = {
        ready: 'bg-green-100 text-green-800',
        generating: 'bg-yellow-100 text-yellow-800',
        pending: 'bg-gray-100 text-gray-800',
        error: 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const statusLabel = (status) => {
    const labels = {
        ready: 'Pret',
        generating: 'En cours',
        pending: 'En attente',
        error: 'Erreur',
    }
    return labels[status] || status
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>
