<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    RocketLaunchIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    CalendarIcon,
    CubeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    demos: Object,
    stats: Object,
})

const showExtendModal = ref(false)
const selectedDemo = ref(null)

const extendForm = useForm({
    additional_days: 7,
})

const openExtendModal = (demo) => {
    selectedDemo.value = demo
    showExtendModal.value = true
}

const extendDemo = () => {
    extendForm.post(route('superadmin.modules.demos.extend', selectedDemo.value.id), {
        onSuccess: () => {
            showExtendModal.value = false
            extendForm.reset()
        },
    })
}

const convertDemo = (demo) => {
    if (confirm(`Convertir cette demo en abonnement ?`)) {
        router.post(route('superadmin.modules.demos.convert', demo.id))
    }
}

const cancelDemo = (demo) => {
    if (confirm(`Annuler cette demo ?`)) {
        router.post(route('superadmin.modules.demos.cancel', demo.id))
    }
}

const getStatusBadge = (status) => {
    const badges = {
        active: { text: 'Active', class: 'bg-green-500/20 text-green-400' },
        expired: { text: 'Expiree', class: 'bg-red-500/20 text-red-400' },
        converted: { text: 'Convertie', class: 'bg-blue-500/20 text-blue-400' },
        cancelled: { text: 'Annulee', class: 'bg-gray-500/20 text-gray-400' },
    }
    return badges[status] || badges.active
}

const getDemoTypeBadge = (type) => {
    const badges = {
        module: { text: 'Module', class: 'bg-purple-500/20 text-purple-400' },
        plan: { text: 'Plan', class: 'bg-blue-500/20 text-blue-400' },
        full_app: { text: 'App Complete', class: 'bg-yellow-500/20 text-yellow-400' },
    }
    return badges[type] || badges.module
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <Head title="Historique des Demos" />

    <SuperAdminLayout title="Historique des Demos">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('superadmin.modules.index')"
                        class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Historique des Demos</h1>
                        <p class="mt-1 text-sm text-gray-400">Suivez et gerez toutes les periodes d'essai</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Demos Actives</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.active_demos }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Converties</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.converted }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Expirees</div>
                    <div class="mt-1 text-2xl font-semibold text-red-400">{{ stats.expired }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Taux de Conversion</div>
                    <div class="mt-1 text-2xl font-semibold text-purple-400">{{ stats.conversion_rate }}%</div>
                </div>
            </div>

            <!-- Demos Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Tenant
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Module/Plan
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Periode
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Cree par
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr v-for="demo in demos.data" :key="demo.id" class="hover:bg-gray-700/30">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <Link
                                        :href="route('superadmin.modules.tenant', demo.tenant?.id)"
                                        class="text-white font-medium hover:text-purple-400"
                                    >
                                        {{ demo.tenant?.name || 'Tenant supprime' }}
                                    </Link>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span :class="[getDemoTypeBadge(demo.demo_type).class, 'px-2 py-1 text-xs rounded-full']">
                                        {{ getDemoTypeBadge(demo.demo_type).text }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <CubeIcon class="h-4 w-4 text-gray-500" />
                                        <span class="text-gray-300">
                                            {{ demo.module?.name || demo.plan?.name || 'Tous les modules' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 text-sm">
                                        <CalendarIcon class="h-4 w-4 text-gray-500" />
                                        <span class="text-gray-300">
                                            {{ formatDate(demo.started_at) }} - {{ formatDate(demo.ends_at) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span :class="[getStatusBadge(demo.status).class, 'px-2 py-1 text-xs rounded-full']">
                                        {{ getStatusBadge(demo.status).text }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-400">
                                    {{ demo.created_by?.name || '-' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right">
                                    <div v-if="demo.status === 'active'" class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openExtendModal(demo)"
                                            class="px-2 py-1 text-xs bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 rounded transition-colors"
                                            title="Prolonger"
                                        >
                                            <ClockIcon class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="convertDemo(demo)"
                                            class="px-2 py-1 text-xs bg-green-600/20 hover:bg-green-600/30 text-green-400 rounded transition-colors"
                                            title="Convertir en abonnement"
                                        >
                                            <CheckCircleIcon class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="cancelDemo(demo)"
                                            class="px-2 py-1 text-xs bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded transition-colors"
                                            title="Annuler"
                                        >
                                            <XCircleIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <span v-else class="text-gray-500 text-sm">-</span>
                                </td>
                            </tr>
                            <tr v-if="demos.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    Aucune demo enregistree
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="demos.links && demos.links.length > 3" class="px-4 py-3 border-t border-gray-700 flex justify-center gap-1">
                    <Link
                        v-for="link in demos.links"
                        :key="link.label"
                        :href="link.url"
                        :class="[
                            link.active ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                            'px-3 py-1 text-sm rounded',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Extend Modal -->
        <div v-if="showExtendModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-white mb-4">
                    Prolonger la Demo
                </h3>

                <div>
                    <label class="text-sm text-gray-400">Jours supplementaires</label>
                    <input
                        v-model="extendForm.additional_days"
                        type="number"
                        min="1"
                        max="30"
                        class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                    />
                </div>

                <div class="mt-6 flex gap-3">
                    <button
                        @click="showExtendModal = false"
                        class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        @click="extendDemo"
                        :disabled="extendForm.processing"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
                    >
                        Prolonger
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
