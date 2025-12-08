<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    Cog6ToothIcon,
    ServerIcon,
    CircleStackIcon,
    CloudIcon,
    ShieldCheckIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    TrashIcon,
    BoltIcon,
    WrenchScrewdriverIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object,
    systemInfo: Object,
    healthChecks: Object,
})

const isLoading = ref({
    clearCache: false,
    optimize: false,
    migrations: false,
    maintenanceDown: false,
    maintenanceUp: false,
})

const clearCache = () => {
    isLoading.value.clearCache = true
    router.post(route('superadmin.settings.clear-cache'), {}, {
        onFinish: () => isLoading.value.clearCache = false
    })
}

const optimizeApplication = () => {
    isLoading.value.optimize = true
    router.post(route('superadmin.settings.optimize'), {}, {
        onFinish: () => isLoading.value.optimize = false
    })
}

const runMigrations = () => {
    if (confirm('Êtes-vous sûr de vouloir exécuter les migrations ? Cela peut modifier la base de données.')) {
        isLoading.value.migrations = true
        router.post(route('superadmin.settings.migrations'), {}, {
            onFinish: () => isLoading.value.migrations = false
        })
    }
}

const enableMaintenance = () => {
    if (confirm('Êtes-vous sûr de vouloir activer le mode maintenance ? Les utilisateurs ne pourront plus accéder au site.')) {
        isLoading.value.maintenanceDown = true
        router.post(route('superadmin.settings.maintenance'), { action: 'down' }, {
            onFinish: () => isLoading.value.maintenanceDown = false
        })
    }
}

const disableMaintenance = () => {
    isLoading.value.maintenanceUp = true
    router.post(route('superadmin.settings.maintenance'), { action: 'up' }, {
        onFinish: () => isLoading.value.maintenanceUp = false
    })
}

const getHealthStatusIcon = (check) => {
    if (check.status === 'ok') return CheckCircleIcon
    if (check.status === 'warning') return ExclamationTriangleIcon
    return ExclamationTriangleIcon
}

const getHealthStatusColor = (check) => {
    if (check.status === 'ok') return 'text-green-400'
    if (check.status === 'warning') return 'text-amber-400'
    return 'text-red-400'
}
</script>

<template>
    <Head title="Paramètres Système" />

    <SuperAdminLayout title="Paramètres">
        <div class="space-y-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold text-white">Paramètres Système</h1>
                <p class="mt-1 text-sm text-gray-400">Configuration et maintenance de la plateforme</p>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4 flex items-center gap-2">
                    <WrenchScrewdriverIcon class="h-5 w-5 text-purple-400" />
                    Actions rapides
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <button
                        @click="clearCache"
                        :disabled="isLoading.clearCache"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        <TrashIcon v-if="!isLoading.clearCache" class="h-5 w-5 text-red-400" />
                        <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                        <span>Vider le cache</span>
                    </button>
                    <button
                        @click="optimizeApplication"
                        :disabled="isLoading.optimize"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        <BoltIcon v-if="!isLoading.optimize" class="h-5 w-5 text-amber-400" />
                        <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                        <span>Optimiser</span>
                    </button>
                    <button
                        @click="runMigrations"
                        :disabled="isLoading.migrations"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-700 hover:bg-gray-600 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        <CircleStackIcon v-if="!isLoading.migrations" class="h-5 w-5 text-blue-400" />
                        <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                        <span>Migrations</span>
                    </button>
                    <button
                        @click="enableMaintenance"
                        :disabled="isLoading.maintenanceDown"
                        class="flex items-center justify-center gap-2 px-4 py-3 bg-red-600/20 hover:bg-red-600/30 border border-red-600/30 disabled:opacity-50 text-red-400 rounded-lg transition-colors"
                    >
                        <LockClosedIcon v-if="!isLoading.maintenanceDown" class="h-5 w-5" />
                        <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                        <span>Mode maintenance</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- System Health -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4 flex items-center gap-2">
                        <ShieldCheckIcon class="h-5 w-5 text-green-400" />
                        Santé du Système
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="(check, key) in healthChecks"
                            :key="key"
                            class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <component
                                    :is="getHealthStatusIcon(check)"
                                    :class="[getHealthStatusColor(check), 'h-5 w-5']"
                                />
                                <span class="text-sm text-white capitalize">{{ key }}</span>
                            </div>
                            <span :class="[getHealthStatusColor(check), 'text-sm']">
                                {{ check.message }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4 flex items-center gap-2">
                        <ServerIcon class="h-5 w-5 text-blue-400" />
                        Informations Système
                    </h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <dt class="text-sm text-gray-400">Version PHP</dt>
                            <dd class="text-sm text-white font-mono">{{ systemInfo.php_version }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <dt class="text-sm text-gray-400">Version Laravel</dt>
                            <dd class="text-sm text-white font-mono">{{ systemInfo.laravel_version }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <dt class="text-sm text-gray-400">Serveur</dt>
                            <dd class="text-sm text-white font-mono truncate max-w-[200px]">{{ systemInfo.server_software }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-700">
                            <dt class="text-sm text-gray-400">Base de données</dt>
                            <dd class="text-sm text-white font-mono">{{ systemInfo.database }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-sm text-gray-400">Fuseau horaire</dt>
                            <dd class="text-sm text-white font-mono">{{ systemInfo.timezone }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Application Configuration -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4 flex items-center gap-2">
                    <Cog6ToothIcon class="h-5 w-5 text-purple-400" />
                    Configuration Application
                </h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">Nom de l'app</div>
                        <div class="text-sm text-white font-medium">{{ settings.app_name }}</div>
                    </div>
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">URL</div>
                        <div class="text-sm text-white font-medium truncate">{{ settings.app_url }}</div>
                    </div>
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">Environnement</div>
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    settings.app_env === 'production' ? 'bg-green-500/10 text-green-400' : 'bg-amber-500/10 text-amber-400',
                                    'px-2 py-0.5 text-xs rounded-full'
                                ]"
                            >
                                {{ settings.app_env }}
                            </span>
                        </div>
                    </div>
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">Debug</div>
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    settings.app_debug ? 'bg-red-500/10 text-red-400' : 'bg-green-500/10 text-green-400',
                                    'px-2 py-0.5 text-xs rounded-full'
                                ]"
                            >
                                {{ settings.app_debug ? 'Activé' : 'Désactivé' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services Configuration -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4 flex items-center gap-2">
                    <CloudIcon class="h-5 w-5 text-cyan-400" />
                    Services
                </h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">Driver Mail</div>
                        <div class="text-sm text-white font-medium font-mono">{{ settings.mail_driver }}</div>
                    </div>
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">Driver Queue</div>
                        <div class="text-sm text-white font-medium font-mono">{{ settings.queue_driver }}</div>
                    </div>
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">Driver Cache</div>
                        <div class="text-sm text-white font-medium font-mono">{{ settings.cache_driver }}</div>
                    </div>
                    <div class="p-4 bg-gray-700/50 rounded-lg">
                        <div class="text-xs text-gray-400 uppercase mb-1">Driver Session</div>
                        <div class="text-sm text-white font-medium font-mono">{{ settings.session_driver }}</div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-red-900/20 rounded-xl p-6 border border-red-600/30">
                <h3 class="text-lg font-medium text-red-400 mb-4 flex items-center gap-2">
                    <ExclamationTriangleIcon class="h-5 w-5" />
                    Zone dangereuse
                </h3>
                <p class="text-sm text-gray-400 mb-4">
                    Ces actions peuvent affecter la disponibilité de votre application. Utilisez-les avec précaution.
                </p>
                <div class="flex flex-wrap gap-4">
                    <button
                        @click="enableMaintenance"
                        :disabled="isLoading.maintenanceDown"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm rounded-lg transition-colors"
                    >
                        Activer le mode maintenance
                    </button>
                    <button
                        @click="disableMaintenance"
                        :disabled="isLoading.maintenanceUp"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm rounded-lg transition-colors"
                    >
                        Désactiver le mode maintenance
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
