<template>
    <TenantLayout title="RGPD & Protection des données" :breadcrumbs="[{ label: 'RGPD' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Demandes en attente</p>
                            <p class="text-3xl font-bold text-orange-600">{{ stats.pending_requests }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <ClockIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">En retard</p>
                            <p class="text-3xl font-bold text-red-600">{{ stats.overdue_requests }}</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-xl">
                            <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Traitées ce mois</p>
                            <p class="text-3xl font-bold text-green-600">{{ stats.completed_this_month }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-xl">
                            <CheckCircleIcon class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Consentements actifs</p>
                            <p class="text-3xl font-bold text-blue-600">{{ stats.active_consents }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <ShieldCheckIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Actions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- GDPR Requests -->
                <Link :href="route('tenant.gdpr.requests')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-100 rounded-xl group-hover:bg-blue-200 transition-colors">
                            <DocumentTextIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Demandes RGPD</h3>
                    <p class="text-sm text-gray-500 mt-1">Gérer les demandes de droits des personnes</p>
                    <div class="mt-4 flex gap-2">
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Accès</span>
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Effacement</span>
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Portabilité</span>
                    </div>
                </Link>

                <!-- Consents -->
                <Link :href="route('tenant.gdpr.consents')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 rounded-xl group-hover:bg-green-200 transition-colors">
                            <CheckBadgeIcon class="w-6 h-6 text-green-600" />
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-green-600 transition-colors" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Consentements</h3>
                    <p class="text-sm text-gray-500 mt-1">Suivi des consentements clients</p>
                    <div class="mt-4 flex gap-2">
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Marketing</span>
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Données</span>
                    </div>
                </Link>

                <!-- Audit Log -->
                <Link :href="route('tenant.gdpr.audit-log')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-100 rounded-xl group-hover:bg-purple-200 transition-colors">
                            <ClipboardDocumentListIcon class="w-6 h-6 text-purple-600" />
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-purple-600 transition-colors" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Journal d'audit</h3>
                    <p class="text-sm text-gray-500 mt-1">Traçabilité des actions sur les données</p>
                    <div class="mt-4 flex gap-2">
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Création</span>
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Modification</span>
                        <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">Export</span>
                    </div>
                </Link>
            </div>

            <!-- Compliance Info -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 text-white">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-white/20 rounded-xl">
                        <InformationCircleIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg">Délais de traitement RGPD</h3>
                        <p class="text-blue-100 mt-1">
                            Conformément au RGPD, vous disposez de <strong>30 jours</strong> pour répondre aux demandes de droits des personnes.
                            Les demandes en retard sont signalées en rouge.
                        </p>
                        <div class="mt-4 flex gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                                <span>Dans les délais</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                <span>&lt; 7 jours restants</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <span>En retard</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Actions rapides</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Link :href="route('tenant.gdpr.requests.create')" class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <PlusIcon class="w-5 h-5 text-gray-500 mr-3" />
                        <span class="text-sm font-medium text-gray-700">Nouvelle demande RGPD</span>
                    </Link>
                    <button @click="showExportModal = true" class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors text-left">
                        <ArrowDownTrayIcon class="w-5 h-5 text-gray-500 mr-3" />
                        <span class="text-sm font-medium text-gray-700">Exporter données client</span>
                    </button>
                    <button class="flex items-center p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors text-left">
                        <DocumentArrowDownIcon class="w-5 h-5 text-gray-500 mr-3" />
                        <span class="text-sm font-medium text-gray-700">Rapport de conformité</span>
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ShieldCheckIcon,
    DocumentTextIcon,
    CheckBadgeIcon,
    ClipboardDocumentListIcon,
    ArrowRightIcon,
    InformationCircleIcon,
    PlusIcon,
    ArrowDownTrayIcon,
    DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline'

defineProps({
    stats: Object,
})

const showExportModal = ref(false)
</script>
