<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    CubeIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlayIcon,
    PauseIcon,
    RocketLaunchIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tenant: Object,
    modules: Array,
    tenantModules: Array,
    plans: Array,
    currentPlan: Object,
})

const showEnableModal = ref(false)
const showDemoModal = ref(false)
const selectedModule = ref(null)

const enableForm = useForm({
    module_id: null,
    is_trial: false,
    trial_days: 14,
    billing_cycle: 'monthly',
    custom_price: null,
})

const demoForm = useForm({
    days: 14,
    notes: '',
})

const planForm = useForm({
    plan_id: props.currentPlan?.id || null,
    billing_cycle: 'monthly',
})

const openEnableModal = (module) => {
    selectedModule.value = module
    enableForm.module_id = module.id
    enableForm.is_trial = false
    enableForm.trial_days = 14
    showEnableModal.value = true
}

const enableModule = () => {
    enableForm.post(route('superadmin.modules.tenant.enable', props.tenant.id), {
        onSuccess: () => {
            showEnableModal.value = false
            enableForm.reset()
        },
    })
}

const disableModule = (module) => {
    if (confirm(`Desactiver le module "${module.name}" pour ${props.tenant.name} ?`)) {
        router.delete(route('superadmin.modules.tenant.disable', [props.tenant.id, module.id]))
    }
}

const startFullDemo = () => {
    demoForm.post(route('superadmin.modules.tenant.full-demo', props.tenant.id), {
        onSuccess: () => {
            showDemoModal.value = false
            demoForm.reset()
        },
    })
}

const changePlan = () => {
    if (confirm('Changer le plan de ce tenant ?')) {
        planForm.post(route('superadmin.modules.tenant.change-plan', props.tenant.id))
    }
}

const getModuleStatus = (module) => {
    if (module.is_enabled) {
        if (module.is_trial) {
            return { text: 'Essai', class: 'bg-yellow-500/20 text-yellow-400' }
        }
        if (module.is_included_in_plan) {
            return { text: 'Plan', class: 'bg-blue-500/20 text-blue-400' }
        }
        if (module.is_core) {
            return { text: 'Core', class: 'bg-gray-500/20 text-gray-400' }
        }
        return { text: 'Actif', class: 'bg-green-500/20 text-green-400' }
    }
    return { text: 'Inactif', class: 'bg-red-500/20 text-red-400' }
}
</script>

<template>
    <Head :title="`Modules - ${tenant.name}`" />

    <SuperAdminLayout :title="`Modules de ${tenant.name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('superadmin.tenants.show', tenant.id)"
                        class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ tenant.name }}</h1>
                        <p class="mt-1 text-sm text-gray-400">Gestion des modules et du plan d'abonnement</p>
                    </div>
                </div>
                <button
                    @click="showDemoModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                >
                    <RocketLaunchIcon class="h-5 w-5" />
                    Demo Complete
                </button>
            </div>

            <!-- Current Plan -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Plan Actuel</h2>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-white">
                            {{ currentPlan?.name || 'Aucun plan' }}
                        </div>
                        <p v-if="currentPlan" class="text-sm text-gray-400 mt-1">
                            {{ currentPlan.monthly_price }} EUR/mois - {{ currentPlan.included_modules?.length || 0 }} modules inclus
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <select
                            v-model="planForm.plan_id"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                        >
                            <option :value="null">Aucun plan</option>
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }} - {{ plan.monthly_price }} EUR/mois
                            </option>
                        </select>
                        <select
                            v-model="planForm.billing_cycle"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                        >
                            <option value="monthly">Mensuel</option>
                            <option value="yearly">Annuel</option>
                        </select>
                        <button
                            @click="changePlan"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
                        >
                            Changer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modules Grid -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Modules Disponibles</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="module in modules"
                        :key="module.id"
                        :class="[
                            'rounded-xl border p-4 transition-colors',
                            module.is_enabled ? 'bg-gray-700/50 border-green-500/50' : 'bg-gray-700/30 border-gray-600'
                        ]"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <div :class="[
                                    module.is_enabled ? 'bg-green-500/20 text-green-400' : 'bg-gray-600/20 text-gray-400',
                                    'p-2 rounded-lg'
                                ]">
                                    <CubeIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="font-medium text-white">{{ module.name }}</h3>
                                    <p class="text-xs text-gray-500">{{ module.code }}</p>
                                </div>
                            </div>
                            <span :class="[getModuleStatus(module).class, 'px-2 py-0.5 text-xs rounded-full']">
                                {{ getModuleStatus(module).text }}
                            </span>
                        </div>

                        <!-- Trial info -->
                        <div v-if="module.is_trial && module.days_remaining !== null" class="mt-3 flex items-center gap-2 text-yellow-400 text-sm">
                            <ClockIcon class="h-4 w-4" />
                            {{ module.days_remaining }} jours restants
                        </div>

                        <!-- Prix -->
                        <div class="mt-2 text-sm text-gray-400">
                            <span v-if="module.is_core">Inclus (core)</span>
                            <span v-else-if="module.monthly_price > 0">{{ module.monthly_price }} EUR/mois</span>
                            <span v-else>Gratuit</span>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex gap-2">
                            <button
                                v-if="!module.is_enabled && !module.is_core"
                                @click="openEnableModal(module)"
                                class="flex-1 flex items-center justify-center gap-1 px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
                            >
                                <PlayIcon class="h-4 w-4" />
                                Activer
                            </button>
                            <button
                                v-if="module.is_enabled && !module.is_core && !module.is_included_in_plan"
                                @click="disableModule(module)"
                                class="flex-1 flex items-center justify-center gap-1 px-3 py-1.5 text-sm bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                            >
                                <PauseIcon class="h-4 w-4" />
                                Desactiver
                            </button>
                            <span
                                v-if="module.is_included_in_plan"
                                class="flex-1 text-center text-sm text-blue-400 py-1.5"
                            >
                                Inclus dans le plan
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enable Module Modal -->
        <div v-if="showEnableModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-white mb-4">
                    Activer "{{ selectedModule?.name }}"
                </h3>

                <div class="space-y-4">
                    <label class="flex items-center gap-3">
                        <input
                            v-model="enableForm.is_trial"
                            type="checkbox"
                            class="rounded border-gray-500 text-purple-600 focus:ring-purple-500"
                        />
                        <span class="text-gray-300">Periode d'essai</span>
                    </label>

                    <div v-if="enableForm.is_trial">
                        <label class="text-sm text-gray-400">Duree de l'essai (jours)</label>
                        <input
                            v-model="enableForm.trial_days"
                            type="number"
                            min="1"
                            max="90"
                            class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                        />
                    </div>

                    <div>
                        <label class="text-sm text-gray-400">Cycle de facturation</label>
                        <select
                            v-model="enableForm.billing_cycle"
                            class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                        >
                            <option value="monthly">Mensuel</option>
                            <option value="yearly">Annuel</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm text-gray-400">Prix personnalise (optionnel)</label>
                        <input
                            v-model="enableForm.custom_price"
                            type="number"
                            step="0.01"
                            placeholder="Laisser vide pour le prix par defaut"
                            class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                        />
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button
                        @click="showEnableModal = false"
                        class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        @click="enableModule"
                        :disabled="enableForm.processing"
                        class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ enableForm.is_trial ? 'Demarrer l\'essai' : 'Activer' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Full Demo Modal -->
        <div v-if="showDemoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-white mb-4">
                    Demo Complete pour {{ tenant.name }}
                </h3>
                <p class="text-gray-400 mb-4">
                    Cette action activera TOUS les modules en mode demo pour la duree specifiee.
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-400">Duree de la demo (jours)</label>
                        <input
                            v-model="demoForm.days"
                            type="number"
                            min="1"
                            max="90"
                            class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                        />
                    </div>

                    <div>
                        <label class="text-sm text-gray-400">Notes (optionnel)</label>
                        <textarea
                            v-model="demoForm.notes"
                            rows="3"
                            class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                            placeholder="Raison de la demo, contact commercial..."
                        ></textarea>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button
                        @click="showDemoModal = false"
                        class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        @click="startFullDemo"
                        :disabled="demoForm.processing"
                        class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors disabled:opacity-50"
                    >
                        Demarrer la Demo
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
