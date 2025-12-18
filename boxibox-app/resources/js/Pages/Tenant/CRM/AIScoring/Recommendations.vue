<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    LightBulbIcon,
    FireIcon,
    ClockIcon,
    ArrowTrendingDownIcon,
    BoltIcon,
    PhoneIcon,
    EnvelopeIcon,
    CalendarIcon,
    ExclamationTriangleIcon,
    ChartBarIcon,
    SparklesIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    urgentLeads: Array,
    decliningLeads: Array,
    highProbabilityNew: Array,
    urgentTiming: Array,
    stats: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
    })
}

const daysUntil = (date) => {
    if (!date) return null
    const d = new Date(date)
    const today = new Date()
    const diff = Math.ceil((d - today) / (1000 * 60 * 60 * 24))
    return diff
}
</script>

<template>
    <TenantLayout title="Recommandations IA">
        <div class="space-y-6">
            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <LightBulbIcon class="h-8 w-8 text-white" />
                                </div>
                                Recommandations IA
                            </h1>
                            <p class="mt-2 text-orange-100">
                                Actions prioritaires pour maximiser vos conversions
                            </p>
                        </div>
                        <Link
                            :href="route('tenant.crm.ai-scoring.dashboard')"
                            class="inline-flex items-center px-6 py-2.5 bg-white text-orange-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <ChartBarIcon class="h-5 w-5 mr-2" />
                            Dashboard IA
                        </Link>
                    </div>

                    <!-- Stats rapides -->
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ urgentLeads?.length || 0 }}</p>
                            <p class="text-xs text-orange-100">A contacter d'urgence</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ decliningLeads?.length || 0 }}</p>
                            <p class="text-xs text-orange-100">Engagement en baisse</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ highProbabilityNew?.length || 0 }}</p>
                            <p class="text-xs text-orange-100">Haute proba. non contactes</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ urgentTiming?.length || 0 }}</p>
                            <p class="text-xs text-orange-100">Timing urgent</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Leads urgents a contacter -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-red-50 to-orange-50">
                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                            <FireIconSolid class="h-5 w-5 text-red-500" />
                            A Contacter D'Urgence
                            <span class="text-sm font-normal text-gray-500">Leads tres chauds sans contact recent</span>
                        </h3>
                    </div>
                    <div v-if="urgentLeads?.length > 0" class="divide-y divide-gray-50">
                        <div
                            v-for="lead in urgentLeads"
                            :key="lead.id"
                            class="px-6 py-4 hover:bg-red-50/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center text-white font-bold text-sm">
                                        {{ lead.score }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ lead.first_name }} {{ lead.last_name }}</p>
                                        <p class="text-sm text-gray-500">{{ lead.email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a
                                        v-if="lead.phone"
                                        :href="`tel:${lead.phone}`"
                                        class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors"
                                    >
                                        <PhoneIcon class="h-5 w-5" />
                                    </a>
                                    <a
                                        :href="`mailto:${lead.email}`"
                                        class="p-2 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-colors"
                                    >
                                        <EnvelopeIcon class="h-5 w-5" />
                                    </a>
                                    <Link
                                        :href="route('tenant.crm.ai-scoring.leads.show', lead.id)"
                                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <ArrowRightIcon class="h-5 w-5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-gray-500">
                        <FireIcon class="h-8 w-8 mx-auto mb-2 text-gray-300" />
                        <p>Tous les leads chauds ont ete contactes</p>
                    </div>
                </div>

                <!-- Leads avec engagement en baisse -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-yellow-50">
                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                            <ArrowTrendingDownIcon class="h-5 w-5 text-amber-500" />
                            Engagement en Baisse
                            <span class="text-sm font-normal text-gray-500">Pas d'activite depuis 7+ jours</span>
                        </h3>
                    </div>
                    <div v-if="decliningLeads?.length > 0" class="divide-y divide-gray-50">
                        <div
                            v-for="lead in decliningLeads"
                            :key="lead.id"
                            class="px-6 py-4 hover:bg-amber-50/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500 to-yellow-500 flex items-center justify-center text-white font-bold text-sm">
                                        {{ lead.score }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ lead.first_name }} {{ lead.last_name }}</p>
                                        <p class="text-sm text-gray-500">Derniere activite: {{ formatDate(lead.updated_at) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a
                                        :href="`mailto:${lead.email}`"
                                        class="px-3 py-1.5 bg-amber-100 text-amber-700 text-sm font-medium rounded-lg hover:bg-amber-200 transition-colors"
                                    >
                                        Relancer
                                    </a>
                                    <Link
                                        :href="route('tenant.crm.ai-scoring.leads.show', lead.id)"
                                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <ArrowRightIcon class="h-5 w-5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-gray-500">
                        <SparklesIcon class="h-8 w-8 mx-auto mb-2 text-gray-300" />
                        <p>Tous les leads sont actifs</p>
                    </div>
                </div>

                <!-- Haute probabilite non contactes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50">
                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                            <BoltIcon class="h-5 w-5 text-emerald-500" />
                            Haute Probabilite - A Contacter
                            <span class="text-sm font-normal text-gray-500">Proba &ge;60% jamais contactes</span>
                        </h3>
                    </div>
                    <div v-if="highProbabilityNew?.length > 0" class="divide-y divide-gray-50">
                        <div
                            v-for="lead in highProbabilityNew"
                            :key="lead.id"
                            class="px-6 py-4 hover:bg-emerald-50/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center text-white font-bold text-sm">
                                            {{ lead.conversion_probability }}%
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ lead.first_name }} {{ lead.last_name }}</p>
                                        <p class="text-sm text-gray-500">Score: {{ lead.score }} | {{ lead.source || 'Direct' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a
                                        v-if="lead.phone"
                                        :href="`tel:${lead.phone}`"
                                        class="px-3 py-1.5 bg-emerald-100 text-emerald-700 text-sm font-medium rounded-lg hover:bg-emerald-200 transition-colors flex items-center gap-1"
                                    >
                                        <PhoneIcon class="h-4 w-4" />
                                        Appeler
                                    </a>
                                    <Link
                                        :href="route('tenant.crm.ai-scoring.leads.show', lead.id)"
                                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <ArrowRightIcon class="h-5 w-5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-gray-500">
                        <BoltIcon class="h-8 w-8 mx-auto mb-2 text-gray-300" />
                        <p>Tous les leads haute proba ont ete contactes</p>
                    </div>
                </div>

                <!-- Timing urgent -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                            <CalendarIcon class="h-5 w-5 text-purple-500" />
                            Timing Urgent
                            <span class="text-sm font-normal text-gray-500">Besoin dans les 7 prochains jours</span>
                        </h3>
                    </div>
                    <div v-if="urgentTiming?.length > 0" class="divide-y divide-gray-50">
                        <div
                            v-for="lead in urgentTiming"
                            :key="lead.id"
                            class="px-6 py-4 hover:bg-purple-50/50 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white">
                                        <ClockIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ lead.first_name }} {{ lead.last_name }}</p>
                                        <p class="text-sm text-gray-500">
                                            <template v-if="daysUntil(lead.move_in_date) === 0">
                                                <span class="text-red-600 font-medium">Aujourd'hui!</span>
                                            </template>
                                            <template v-else-if="daysUntil(lead.move_in_date) === 1">
                                                <span class="text-orange-600 font-medium">Demain</span>
                                            </template>
                                            <template v-else>
                                                Dans {{ daysUntil(lead.move_in_date) }} jours ({{ formatDate(lead.move_in_date) }})
                                            </template>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="daysUntil(lead.move_in_date) <= 2 ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700'"
                                    >
                                        J-{{ daysUntil(lead.move_in_date) }}
                                    </span>
                                    <Link
                                        :href="route('tenant.crm.ai-scoring.leads.show', lead.id)"
                                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <ArrowRightIcon class="h-5 w-5" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-gray-500">
                        <CalendarIcon class="h-8 w-8 mx-auto mb-2 text-gray-300" />
                        <p>Aucun lead avec timing urgent</p>
                    </div>
                </div>
            </div>

            <!-- Conseils generaux -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-purple-100 p-6">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <SparklesIcon class="h-5 w-5 text-purple-500" />
                    Conseils de l'IA pour Optimiser vos Conversions
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <ClockIcon class="h-5 w-5 text-amber-500" />
                            <span class="font-medium text-gray-900">Temps de reponse</span>
                        </div>
                        <p class="text-sm text-gray-600">
                            Contactez les leads chauds dans les 2 heures suivant leur creation pour maximiser les conversions.
                        </p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <EnvelopeIcon class="h-5 w-5 text-emerald-500" />
                            <span class="font-medium text-gray-900">Personnalisation</span>
                        </div>
                        <p class="text-sm text-gray-600">
                            Les leads avec email professionnel convertissent 40% mieux. Adaptez votre discours en consequence.
                        </p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center gap-2 mb-2">
                            <FireIcon class="h-5 w-5 text-red-500" />
                            <span class="font-medium text-gray-900">Leads declinants</span>
                        </div>
                        <p class="text-sm text-gray-600">
                            Un lead sans interaction depuis 7 jours perd 50% de sa probabilite de conversion. Relancez-les!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
