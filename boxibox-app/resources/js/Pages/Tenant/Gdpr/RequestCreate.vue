<template>
    <TenantLayout title="Nouvelle demande RGPD" :breadcrumbs="[{ label: 'RGPD', href: route('tenant.gdpr.index') }, { label: 'Demandes', href: route('tenant.gdpr.requests') }, { label: 'Nouvelle' }]">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Request Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de demande *</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <label
                                v-for="type in requestTypes"
                                :key="type.value"
                                :class="form.type === type.value ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                                class="flex flex-col p-4 border-2 rounded-xl cursor-pointer transition-colors"
                            >
                                <input type="radio" v-model="form.type" :value="type.value" class="hidden" />
                                <span :class="form.type === type.value ? 'text-primary-700' : 'text-gray-900'" class="font-medium text-sm">
                                    {{ type.label }}
                                </span>
                                <span class="text-xs text-gray-500 mt-1">{{ type.description }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.type" class="text-red-500 text-sm mt-1">{{ form.errors.type }}</p>
                    </div>

                    <!-- Customer Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
                        <select v-model="form.customer_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner un client</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.full_name }} ({{ customer.email }})
                            </option>
                        </select>
                        <p v-if="form.errors.customer_id" class="text-red-500 text-sm mt-1">{{ form.errors.customer_id }}</p>
                    </div>

                    <!-- Request Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de la demande *</label>
                            <input
                                v-model="form.requested_at"
                                type="date"
                                class="w-full rounded-xl border-gray-200"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Canal de réception</label>
                            <select v-model="form.channel" class="w-full rounded-xl border-gray-200">
                                <option value="email">Email</option>
                                <option value="mail">Courrier</option>
                                <option value="phone">Téléphone</option>
                                <option value="in_person">En personne</option>
                                <option value="portal">Portail client</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description de la demande *</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Décrivez la demande du client en détail..."
                            required
                        ></textarea>
                        <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
                    </div>

                    <!-- Type-specific fields -->
                    <div v-if="form.type === 'rectification'" class="p-4 bg-yellow-50 rounded-xl">
                        <h4 class="font-medium text-yellow-900 mb-3">Informations à rectifier</h4>
                        <div class="space-y-3">
                            <div v-for="(item, index) in form.rectification_items" :key="index" class="flex gap-2">
                                <input
                                    v-model="form.rectification_items[index].field"
                                    type="text"
                                    class="flex-1 rounded-xl border-yellow-200"
                                    placeholder="Champ à modifier"
                                />
                                <input
                                    v-model="form.rectification_items[index].new_value"
                                    type="text"
                                    class="flex-1 rounded-xl border-yellow-200"
                                    placeholder="Nouvelle valeur"
                                />
                                <button type="button" @click="removeRectificationItem(index)" class="p-2 text-red-500 hover:text-red-700">
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                            <button type="button" @click="addRectificationItem" class="text-sm text-yellow-700 hover:text-yellow-900">
                                + Ajouter un champ
                            </button>
                        </div>
                    </div>

                    <div v-if="form.type === 'erasure'" class="p-4 bg-red-50 rounded-xl">
                        <h4 class="font-medium text-red-900 mb-3">Données à effacer</h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.erasure_scope.personal_data" class="rounded text-red-600" />
                                <span class="text-sm text-red-800">Données personnelles</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.erasure_scope.contracts" class="rounded text-red-600" />
                                <span class="text-sm text-red-800">Historique des contrats</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.erasure_scope.invoices" class="rounded text-red-600" />
                                <span class="text-sm text-red-800">Factures</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="form.erasure_scope.communications" class="rounded text-red-600" />
                                <span class="text-sm text-red-800">Communications</span>
                            </label>
                        </div>
                        <p class="text-xs text-red-700 mt-3">
                            Note: Certaines données peuvent être conservées pour des obligations légales (comptabilité, fiscalité).
                        </p>
                    </div>

                    <div v-if="form.type === 'portability'" class="p-4 bg-purple-50 rounded-xl">
                        <h4 class="font-medium text-purple-900 mb-3">Format d'export</h4>
                        <select v-model="form.export_format" class="w-full rounded-xl border-purple-200">
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                            <option value="xml">XML</option>
                        </select>
                    </div>

                    <!-- Proof/Attachment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pièce jointe (preuve de demande)</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center">
                            <DocumentArrowUpIcon class="w-10 h-10 text-gray-400 mx-auto mb-2" />
                            <p class="text-sm text-gray-500">Glissez un fichier ou cliquez pour télécharger</p>
                            <input type="file" class="hidden" />
                        </div>
                    </div>

                    <!-- Internal Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes internes</label>
                        <textarea
                            v-model="form.internal_notes"
                            rows="2"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Notes internes (non visibles par le client)..."
                        ></textarea>
                    </div>

                    <!-- Due Date Info -->
                    <div class="p-4 bg-blue-50 rounded-xl">
                        <div class="flex items-center gap-2 text-blue-800">
                            <ClockIcon class="w-5 h-5" />
                            <span class="text-sm">
                                Date limite de traitement: <strong>{{ calculatedDueDate }}</strong> (30 jours)
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('tenant.gdpr.requests')" class="btn-secondary">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <span v-if="form.processing">Création...</span>
                            <span v-else>Créer la demande</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    TrashIcon,
    DocumentArrowUpIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customers: Array,
})

const form = useForm({
    type: '',
    customer_id: '',
    requested_at: new Date().toISOString().split('T')[0],
    channel: 'email',
    description: '',
    rectification_items: [{ field: '', new_value: '' }],
    erasure_scope: {
        personal_data: true,
        contracts: false,
        invoices: false,
        communications: false,
    },
    export_format: 'json',
    internal_notes: '',
})

const requestTypes = [
    { value: 'access', label: 'Droit d\'accès', description: 'Copie des données personnelles' },
    { value: 'rectification', label: 'Rectification', description: 'Correction de données erronées' },
    { value: 'erasure', label: 'Effacement', description: 'Suppression des données' },
    { value: 'portability', label: 'Portabilité', description: 'Export des données' },
    { value: 'restriction', label: 'Limitation', description: 'Gel du traitement' },
    { value: 'objection', label: 'Opposition', description: 'Opposition au traitement' },
]

const calculatedDueDate = computed(() => {
    const date = new Date(form.requested_at)
    date.setDate(date.getDate() + 30)
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
})

const addRectificationItem = () => {
    form.rectification_items.push({ field: '', new_value: '' })
}

const removeRectificationItem = (index) => {
    form.rectification_items.splice(index, 1)
}

const submit = () => {
    form.post(route('tenant.gdpr.requests.store'))
}
</script>
