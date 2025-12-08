<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    CurrencyEuroIcon,
    UsersIcon,
    BuildingStorefrontIcon,
    CubeIcon,
    CheckIcon,
    XMarkIcon,
    StarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    plans: Array,
    modules: Array,
})

const editingPlan = ref(null)
const form = useForm({
    name: '',
    description: '',
    badge_color: '',
    monthly_price: 0,
    yearly_price: 0,
    yearly_discount: 20,
    max_sites: null,
    max_boxes: null,
    max_users: null,
    max_customers: null,
    includes_support: true,
    support_level: 'email',
    included_modules: [],
    features: [],
    is_popular: false,
    is_active: true,
})

const startEdit = (plan) => {
    editingPlan.value = plan.id
    form.name = plan.name
    form.description = plan.description
    form.badge_color = plan.badge_color
    form.monthly_price = plan.monthly_price
    form.yearly_price = plan.yearly_price
    form.yearly_discount = plan.yearly_discount
    form.max_sites = plan.max_sites
    form.max_boxes = plan.max_boxes
    form.max_users = plan.max_users
    form.max_customers = plan.max_customers
    form.includes_support = plan.includes_support
    form.support_level = plan.support_level
    form.included_modules = plan.included_modules || []
    form.features = plan.features || []
    form.is_popular = plan.is_popular
    form.is_active = plan.is_active
}

const cancelEdit = () => {
    editingPlan.value = null
    form.reset()
}

const savePlan = (plan) => {
    form.put(route('superadmin.modules.plans.update', plan.id), {
        onSuccess: () => {
            editingPlan.value = null
        },
    })
}

const toggleModule = (moduleId) => {
    const index = form.included_modules.indexOf(moduleId)
    if (index > -1) {
        form.included_modules.splice(index, 1)
    } else {
        form.included_modules.push(moduleId)
    }
}

const getPlanColor = (code) => {
    const colors = {
        starter: 'blue',
        professional: 'purple',
        business: 'orange',
        enterprise: 'yellow',
    }
    return colors[code] || 'gray'
}
</script>

<template>
    <Head title="Gestion des Plans" />

    <SuperAdminLayout title="Plans d'abonnement">
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
                        <h1 class="text-2xl font-bold text-white">Plans d'Abonnement</h1>
                        <p class="mt-1 text-sm text-gray-400">Configurez les tarifs et les modules inclus dans chaque plan</p>
                    </div>
                </div>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        'bg-gray-800 rounded-xl border-2 p-6 transition-all',
                        plan.is_popular ? 'border-purple-500' : 'border-gray-700'
                    ]"
                >
                    <!-- Plan Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                                <span v-if="plan.is_popular" class="px-2 py-0.5 text-xs bg-purple-500 text-white rounded-full flex items-center gap-1">
                                    <StarIcon class="h-3 w-3" />
                                    Populaire
                                </span>
                            </div>
                            <p class="text-sm text-gray-400 mt-1">{{ plan.description }}</p>
                        </div>
                        <button
                            v-if="editingPlan !== plan.id"
                            @click="startEdit(plan)"
                            class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                        >
                            <PencilSquareIcon class="h-5 w-5 text-gray-300" />
                        </button>
                        <div v-else class="flex gap-2">
                            <button
                                @click="savePlan(plan)"
                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors"
                            >
                                Sauvegarder
                            </button>
                            <button
                                @click="cancelEdit"
                                class="px-3 py-1.5 bg-gray-600 hover:bg-gray-500 text-white text-sm rounded-lg transition-colors"
                            >
                                Annuler
                            </button>
                        </div>
                    </div>

                    <!-- Editing Mode -->
                    <div v-if="editingPlan === plan.id" class="space-y-4">
                        <!-- Prix -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-400">Prix Mensuel (EUR)</label>
                                <input
                                    v-model="form.monthly_price"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-400">Prix Annuel (EUR)</label>
                                <input
                                    v-model="form.yearly_price"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                                />
                            </div>
                        </div>

                        <!-- Limites -->
                        <div class="grid grid-cols-4 gap-4">
                            <div>
                                <label class="text-sm text-gray-400">Max Sites</label>
                                <input
                                    v-model="form.max_sites"
                                    type="number"
                                    placeholder="Illimite"
                                    class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-400">Max Boxes</label>
                                <input
                                    v-model="form.max_boxes"
                                    type="number"
                                    placeholder="Illimite"
                                    class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-400">Max Users</label>
                                <input
                                    v-model="form.max_users"
                                    type="number"
                                    placeholder="Illimite"
                                    class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                                />
                            </div>
                            <div>
                                <label class="text-sm text-gray-400">Max Clients</label>
                                <input
                                    v-model="form.max_customers"
                                    type="number"
                                    placeholder="Illimite"
                                    class="mt-1 w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
                                />
                            </div>
                        </div>

                        <!-- Modules Inclus -->
                        <div>
                            <label class="text-sm text-gray-400">Modules Inclus</label>
                            <div class="mt-2 grid grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                                <label
                                    v-for="module in modules"
                                    :key="module.id"
                                    :class="[
                                        form.included_modules.includes(module.id) ? 'bg-purple-600/20 border-purple-500' : 'bg-gray-700 border-gray-600',
                                        'flex items-center gap-2 p-2 rounded-lg border cursor-pointer hover:border-purple-400 transition-colors'
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="form.included_modules.includes(module.id)"
                                        @change="toggleModule(module.id)"
                                        class="rounded border-gray-500 text-purple-600 focus:ring-purple-500"
                                    />
                                    <span class="text-sm text-white">{{ module.name }}</span>
                                    <span v-if="module.is_core" class="text-xs text-blue-400">(Core)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="form.is_popular"
                                    type="checkbox"
                                    class="rounded border-gray-500 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm text-gray-300">Marquer comme populaire</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-500 text-purple-600 focus:ring-purple-500"
                                />
                                <span class="text-sm text-gray-300">Plan actif</span>
                            </label>
                        </div>
                    </div>

                    <!-- View Mode -->
                    <div v-else>
                        <!-- Prix -->
                        <div class="flex items-baseline gap-2 mb-4">
                            <span class="text-3xl font-bold text-white">{{ plan.monthly_price }} EUR</span>
                            <span class="text-gray-500">/mois</span>
                        </div>
                        <div class="text-sm text-gray-400 mb-4">
                            ou {{ plan.yearly_price }} EUR/an ({{ plan.yearly_discount }}% d'economie)
                        </div>

                        <!-- Limites -->
                        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                            <div class="flex items-center gap-2 text-gray-300">
                                <BuildingStorefrontIcon class="h-4 w-4 text-gray-500" />
                                <span>{{ plan.max_sites || 'Illimite' }} site(s)</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <CubeIcon class="h-4 w-4 text-gray-500" />
                                <span>{{ plan.max_boxes || 'Illimite' }} boxes</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <UsersIcon class="h-4 w-4 text-gray-500" />
                                <span>{{ plan.max_users || 'Illimite' }} utilisateurs</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <UsersIcon class="h-4 w-4 text-gray-500" />
                                <span>{{ plan.max_customers || 'Illimite' }} clients</span>
                            </div>
                        </div>

                        <!-- Modules Inclus -->
                        <div class="border-t border-gray-700 pt-4">
                            <div class="text-sm font-medium text-gray-300 mb-2">
                                {{ plan.included_modules_list?.length || 0 }} modules inclus
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="mod in plan.included_modules_list"
                                    :key="mod.id"
                                    class="px-2 py-0.5 text-xs bg-gray-700 text-gray-300 rounded-full"
                                >
                                    {{ mod.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="mt-4 pt-4 border-t border-gray-700 text-sm text-gray-500">
                            <span class="text-purple-400 font-medium">{{ plan.tenant_count }}</span> tenant(s) sur ce plan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
