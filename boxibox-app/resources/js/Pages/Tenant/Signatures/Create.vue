<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    contracts: Array,
})

const form = useForm({
    contract_id: '',
    type: 'contract',
    email: '',
    expires_in_days: 30,
    notes: '',
})

const submit = () => {
    form.post(route('tenant.signatures.store'))
}

const selectContract = (contractId) => {
    form.contract_id = contractId
    const contract = props.contracts.find(c => c.id === contractId)
    if (contract && contract.customer) {
        form.email = contract.customer.email
    }
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <AuthenticatedLayout title="Nouvelle demande de signature">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Nouvelle demande de signature</h2>
                    <p class="mt-1 text-sm text-gray-500">Envoyez un contrat ou un mandat SEPA à signer</p>
                </div>
                <Link
                    :href="route('tenant.signatures.index')"
                    class="text-gray-600 hover:text-gray-900"
                >
                    &larr; Retour à la liste
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Contract Selection -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contrat à signer</h3>

                    <div v-if="contracts.length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mb-4">Aucun contrat en attente de signature</p>
                        <Link :href="route('tenant.contracts.create')" class="text-primary-600 hover:text-primary-700">
                            Créer un nouveau contrat
                        </Link>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="contract in contracts"
                            :key="contract.id"
                            @click="selectContract(contract.id)"
                            :class="[
                                'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                                form.contract_id === contract.id
                                    ? 'border-primary-500 bg-primary-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900">{{ contract.contract_number }}</p>
                                    <p class="text-sm text-gray-600">{{ getCustomerName(contract.customer) }}</p>
                                    <p class="text-sm text-gray-500">Box: {{ contract.box?.code || 'N/A' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">{{ contract.monthly_price?.toFixed(2) }} EUR/mois</p>
                                    <p class="text-sm text-gray-500">{{ contract.customer?.email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="form.errors.contract_id" class="mt-2 text-sm text-red-600">{{ form.errors.contract_id }}</p>
                </div>

                <!-- Type Selection -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Type de document</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label
                            :class="[
                                'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                                form.type === 'contract'
                                    ? 'border-primary-500 bg-primary-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <input type="radio" v-model="form.type" value="contract" class="hidden" />
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Contrat de location</p>
                                    <p class="text-sm text-gray-500">Contrat de location de box de stockage</p>
                                </div>
                            </div>
                        </label>

                        <label
                            :class="[
                                'p-4 border-2 rounded-lg cursor-pointer transition-colors',
                                form.type === 'mandate'
                                    ? 'border-primary-500 bg-primary-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <input type="radio" v-model="form.type" value="mandate" class="hidden" />
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Mandat SEPA</p>
                                    <p class="text-sm text-gray-500">Autorisation de prélèvement automatique</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    <p v-if="form.errors.type" class="mt-2 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Email & Settings -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres d'envoi</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email du signataire *</label>
                            <input
                                type="email"
                                v-model="form.email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="client@email.com"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Validité (jours)</label>
                            <select
                                v-model="form.expires_in_days"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            >
                                <option :value="7">7 jours</option>
                                <option :value="14">14 jours</option>
                                <option :value="30">30 jours</option>
                                <option :value="60">60 jours</option>
                                <option :value="90">90 jours</option>
                            </select>
                            <p v-if="form.errors.expires_in_days" class="mt-1 text-sm text-red-600">{{ form.errors.expires_in_days }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes internes</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Notes visibles uniquement par l'équipe..."
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end space-x-4">
                    <Link
                        :href="route('tenant.signatures.index')"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.contract_id"
                        class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Création...' : 'Créer la demande' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
