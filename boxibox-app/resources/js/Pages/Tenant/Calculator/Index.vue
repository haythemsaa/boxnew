<template>
    <TenantLayout title="Calculateur de stockage" :breadcrumbs="[{ label: 'Calculateur' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Sessions totales</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.total_sessions }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <CalculatorIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Conversions</p>
                            <p class="text-3xl font-bold text-green-600">{{ stats.converted_sessions }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-xl">
                            <UserPlusIcon class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Taux de conversion</p>
                            <p class="text-3xl font-bold text-purple-600">{{ stats.conversion_rate }}%</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <ChartBarIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Volume moyen</p>
                            <p class="text-3xl font-bold text-orange-600">{{ stats.avg_volume.toFixed(1) }} m³</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <CubeIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Gérer le calculateur</h3>
                        <p class="text-sm text-gray-500">Configurez les catégories, articles et widgets d'intégration</p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('tenant.calculator.categories')" class="btn-secondary">
                            <TagIcon class="w-4 h-4 mr-2" />
                            Catégories & Articles
                        </Link>
                        <Link :href="route('tenant.calculator.widgets')" class="btn-primary">
                            <CodeBracketIcon class="w-4 h-4 mr-2" />
                            Widgets
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Widgets List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Mes widgets</h3>
                </div>

                <div v-if="widgets.length === 0" class="px-6 py-12 text-center">
                    <CodeBracketIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucun widget créé</p>
                    <Link :href="route('tenant.calculator.widgets')" class="text-primary-600 hover:text-primary-800 text-sm mt-2 inline-block">
                        Créer mon premier widget
                    </Link>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="widget in widgets" :key="widget.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center" :style="{ backgroundColor: widget.theme_color + '20' }">
                                    <CalculatorIcon class="w-5 h-5" :style="{ color: widget.theme_color }" />
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ widget.name }}</h4>
                                    <p class="text-sm text-gray-500">
                                        {{ widget.site?.name || 'Tous les sites' }} -
                                        {{ widget.sessions_count }} sessions
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span :class="widget.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="px-3 py-1 rounded-full text-xs font-medium">
                                    {{ widget.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <button @click="copyEmbedCode(widget)" class="text-gray-500 hover:text-gray-700" title="Copier le code">
                                    <ClipboardDocumentIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Embed Code Preview -->
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <code class="text-xs text-gray-600 break-all">
                                {{ getEmbedCode(widget) }}
                            </code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sessions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Sessions récentes</h3>
                    <Link :href="route('tenant.calculator.sessions')" class="text-primary-600 hover:text-primary-800 text-sm">
                        Voir toutes
                    </Link>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="session in recentSessions" :key="session.id" class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ session.visitor_name || 'Visiteur anonyme' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ session.total_volume_m3.toFixed(1) }} m³ -
                                Recommandé: {{ session.recommended_size_m2.toFixed(1) }} m²
                            </p>
                        </div>
                        <div class="text-right">
                            <span v-if="session.converted_to_lead" class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">
                                Converti
                            </span>
                            <p class="text-xs text-gray-500 mt-1">{{ formatDate(session.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    CalculatorIcon,
    UserPlusIcon,
    ChartBarIcon,
    CubeIcon,
    TagIcon,
    CodeBracketIcon,
    ClipboardDocumentIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    widgets: Array,
    stats: Object,
    recentSessions: Array,
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getEmbedCode = (widget) => {
    const url = `${window.location.origin}/widget/calculator/${widget.embed_code}`
    return `<iframe src="${url}" width="100%" height="600" frameborder="0"></iframe>`
}

const copyEmbedCode = (widget) => {
    navigator.clipboard.writeText(getEmbedCode(widget))
    alert('Code copié !')
}
</script>
