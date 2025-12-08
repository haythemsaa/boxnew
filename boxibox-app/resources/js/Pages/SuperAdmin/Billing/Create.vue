<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    tenants: Array,
})

const planPrices = {
    free: 0,
    starter: 29,
    professional: 79,
    enterprise: 199,
}

const form = useForm({
    tenant_id: '',
    plan: 'starter',
    amount: 29,
    period_start: new Date().toISOString().split('T')[0],
    period_end: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    notes: '',
})

watch(() => form.plan, (newPlan) => {
    form.amount = planPrices[newPlan] || 0
})

const selectedTenant = computed(() => {
    return props.tenants.find(t => t.id === parseInt(form.tenant_id))
})

const submit = () => {
    form.post(route('superadmin.billing.store'))
}
</script>

<template>
    <Head title="Nouvelle Facture" />

    <SuperAdminLayout title="Nouvelle Facture">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('superadmin.billing.index')"
                    class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-white">Nouvelle Facture</h1>
                    <p class="mt-1 text-sm text-gray-400">Créer une facture d'abonnement</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Tenant Selection -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Tenant</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Sélectionner le tenant *</label>
                        <select
                            v-model="form.tenant_id"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">-- Choisir un tenant --</option>
                            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                {{ tenant.name }} ({{ tenant.plan }})
                            </option>
                        </select>
                        <p v-if="form.errors.tenant_id" class="mt-1 text-sm text-red-400">{{ form.errors.tenant_id }}</p>
                    </div>

                    <div v-if="selectedTenant" class="mt-4 p-4 bg-gray-900 rounded-lg">
                        <div class="text-sm text-gray-400">Tenant sélectionné</div>
                        <div class="mt-1 text-white font-medium">{{ selectedTenant.name }}</div>
                        <div class="mt-1 text-sm text-gray-400">Plan actuel: <span class="text-purple-400 capitalize">{{ selectedTenant.plan }}</span></div>
                    </div>
                </div>

                <!-- Plan & Amount -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Plan & Montant</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Plan *</label>
                            <select
                                v-model="form.plan"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="free">Free - 0€</option>
                                <option value="starter">Starter - 29€</option>
                                <option value="professional">Professional - 79€</option>
                                <option value="enterprise">Enterprise - 199€</option>
                            </select>
                            <p v-if="form.errors.plan" class="mt-1 text-sm text-red-400">{{ form.errors.plan }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Montant (€) *</label>
                            <input
                                v-model.number="form.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.amount" class="mt-1 text-sm text-red-400">{{ form.errors.amount }}</p>
                        </div>
                    </div>
                </div>

                <!-- Period & Due Date -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Période & Échéance</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Début de période *</label>
                            <input
                                v-model="form.period_start"
                                type="date"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.period_start" class="mt-1 text-sm text-red-400">{{ form.errors.period_start }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Fin de période *</label>
                            <input
                                v-model="form.period_end"
                                type="date"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.period_end" class="mt-1 text-sm text-red-400">{{ form.errors.period_end }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Date d'échéance *</label>
                            <input
                                v-model="form.due_date"
                                type="date"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.due_date" class="mt-1 text-sm text-red-400">{{ form.errors.due_date }}</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Notes (optionnel)</h2>

                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                        placeholder="Notes internes ou commentaires..."
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('superadmin.billing.index')"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        Créer la facture
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
