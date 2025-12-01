<template>
    <TenantLayout title="Signer le contrat">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.contracts.show', contract.id)"
                        class="text-sm text-primary-600 hover:text-primary-900 mb-4 inline-flex items-center"
                    >
                        ← Retour au contrat
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900">Signature du contrat</h1>
                    <p class="mt-2 text-gray-600">Contrat #{{ contract.contract_number }}</p>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left: Contract Details -->
                    <div class="lg:col-span-2">
                        <!-- Contract Summary -->
                        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Résumé du contrat</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Box -->
                                <div class="border-l-4 border-blue-500 pl-4">
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Box</p>
                                    <p class="text-lg font-bold text-gray-900">{{ contract.box?.number }}</p>
                                    <p class="text-sm text-gray-600">{{ contract.box?.volume }}m³</p>
                                </div>
                                <!-- Customer -->
                                <div class="border-l-4 border-purple-500 pl-4">
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Client</p>
                                    <p class="text-lg font-bold text-gray-900">{{ getCustomerName(contract.customer) }}</p>
                                    <p class="text-sm text-gray-600">{{ contract.customer?.email }}</p>
                                </div>
                                <!-- Dates -->
                                <div class="border-l-4 border-green-500 pl-4">
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Dates</p>
                                    <p class="text-lg font-bold text-gray-900">{{ formatDate(contract.start_date) }}</p>
                                    <p class="text-sm text-gray-600">à {{ formatDate(contract.end_date) }}</p>
                                </div>
                                <!-- Price -->
                                <div class="border-l-4 border-amber-500 pl-4">
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Prix</p>
                                    <p class="text-lg font-bold text-gray-900">{{ contract.monthly_price }}€/mois</p>
                                    <p v-if="contract.deposit_amount" class="text-sm text-gray-600">
                                        Dépôt: {{ contract.deposit_amount }}€
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                            <h2 class="text-xl font-bold text-gray-900 mb-6">Conditions généales</h2>
                            <div class="prose prose-sm max-w-none">
                                <p>
                                    Le présent contrat est établi entre <strong>{{ $page.props.auth.user?.name }}</strong> (le loueur)
                                    et <strong>{{ getCustomerName(contract.customer) }}</strong> (le locataire).
                                </p>
                                <h3>1. Objet du contrat</h3>
                                <p>
                                    Le loueur met à disposition du locataire un box de stockage numéro
                                    <strong>{{ contract.box?.number }}</strong> d'une capacité de
                                    <strong>{{ contract.box?.volume }}m³</strong>.
                                </p>
                                <h3>2. Durée et dates</h3>
                                <p>
                                    La durée du contrat débute le <strong>{{ formatDate(contract.start_date) }}</strong> et
                                    prend fin le <strong>{{ formatDate(contract.end_date) }}</strong>.
                                </p>
                                <h3>3. Loyer et conditions de paiement</h3>
                                <p>
                                    Le loyer mensuel est fixé à <strong>{{ contract.monthly_price }}€</strong>. Le paiement
                                    s'effectue <strong>{{ contract.billing_frequency }}</strong> par
                                    <strong>{{ getPaymentMethod(contract.payment_method) }}</strong>.
                                </p>
                                <h3>4. Dépôt de garantie</h3>
                                <p v-if="contract.deposit_amount">
                                    Un dépôt de garantie de <strong>{{ contract.deposit_amount }}€</strong> est requis.
                                </p>
                                <h3>5. Conditions de résiliation</h3>
                                <p>
                                    Le contrat peut être résilié avec un préavis de
                                    <strong>{{ contract.notice_period_days }} jours</strong>.
                                </p>
                                <h3>6. Responsabilités</h3>
                                <p>
                                    Le locataire est responsable du contenu du box. Le loueur n'est pas responsable de la
                                    détérioration, la perte ou le vol du contenu.
                                </p>
                                <h3>7. Accès</h3>
                                <p>
                                    L'accès au box est accordé 24/7 via le code <strong>{{ contract.access_code }}</strong>.
                                </p>
                            </div>
                        </div>

                        <!-- Signature Sections -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Customer Signature -->
                            <div class="bg-white rounded-2xl shadow-lg p-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                                    <PencilSquareIcon class="h-5 w-5 text-purple-600" />
                                    Signature du client
                                </h3>
                                <div class="mb-6">
                                    <p class="text-sm text-gray-600 mb-4">
                                        Veuillez signer ci-dessous avec votre doigt ou souris.
                                    </p>
                                    <SignaturePad
                                        @signature-saved="saveCustomerSignature"
                                        ref="customerSignaturePad"
                                    />
                                </div>
                                <div v-if="customerSignatureData" class="mb-4">
                                    <p class="text-xs text-gray-500 mb-2">Signature enregistrée le:</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ new Date().toLocaleString('fr-FR') }}</p>
                                </div>
                            </div>

                            <!-- Staff Signature -->
                            <div class="bg-white rounded-2xl shadow-lg p-8">
                                <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                                    <PencilSquareIcon class="h-5 w-5 text-blue-600" />
                                    Signature du personnel
                                </h3>
                                <div class="mb-6">
                                    <p class="text-sm text-gray-600 mb-4">
                                        Signature du représentant de l'entreprise
                                    </p>
                                    <SignaturePad
                                        @signature-saved="saveStaffSignature"
                                        ref="staffSignaturePad"
                                    />
                                </div>
                                <div v-if="staffSignatureData" class="mb-4">
                                    <p class="text-xs text-gray-500 mb-2">Signature enregistrée par:</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user?.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Sidebar -->
                    <div>
                        <!-- Status Card -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Statut</h3>
                            <div
                                :class="[
                                    'inline-block px-4 py-2 rounded-full text-sm font-bold',
                                    getStatusColor(contract.status),
                                ]"
                            >
                                {{ getStatusLabel(contract.status) }}
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="bg-white rounded-2xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Validation</h3>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'w-6 h-6 rounded-full flex items-center justify-center font-bold text-sm',
                                            customerSignatureData
                                                ? 'bg-green-500 text-white'
                                                : 'bg-gray-200 text-gray-600',
                                        ]"
                                    >
                                        {{ customerSignatureData ? '✓' : '1' }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">Signature client</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'w-6 h-6 rounded-full flex items-center justify-center font-bold text-sm',
                                            staffSignatureData
                                                ? 'bg-green-500 text-white'
                                                : 'bg-gray-200 text-gray-600',
                                        ]"
                                    >
                                        {{ staffSignatureData ? '✓' : '2' }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">Signature personnel</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'w-6 h-6 rounded-full flex items-center justify-center font-bold text-sm',
                                            form.confirmed
                                                ? 'bg-green-500 text-white'
                                                : 'bg-gray-200 text-gray-600',
                                        ]"
                                    >
                                        {{ form.confirmed ? '✓' : '3' }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">Confirmation</span>
                                </div>
                            </div>

                            <!-- Confirmation -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input
                                        v-model="form.confirmed"
                                        type="checkbox"
                                        class="w-5 h-5 text-green-600 rounded mt-1"
                                    />
                                    <span class="text-sm text-gray-700">
                                        J'atteste que les deux signatures sont valides et que les conditions du contrat
                                        sont acceptées.
                                    </span>
                                </label>
                            </div>

                            <!-- Submit -->
                            <button
                                @click="submitSignatures"
                                :disabled="!isFormValid"
                                class="w-full mt-6 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-bold"
                            >
                                ✓ Enregistrer les signatures
                            </button>

                            <!-- Back Button -->
                            <Link
                                :href="route('tenant.contracts.show', contract.id)"
                                class="w-full mt-3 px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-bold text-center"
                            >
                                Retour
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import SignaturePad from '@/Components/Signature/SignaturePad.vue'
import { PencilSquareIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
})

const form = useForm({
    customer_signature: null,
    staff_signature: null,
    confirmed: false,
})

const customerSignatureData = ref(null)
const staffSignatureData = ref(null)

const isFormValid = computed(
    () => customerSignatureData.value && staffSignatureData.value && form.confirmed
)

const saveCustomerSignature = (signatureData) => {
    customerSignatureData.value = signatureData
    form.customer_signature = signatureData
}

const saveStaffSignature = (signatureData) => {
    staffSignatureData.value = signatureData
    form.staff_signature = signatureData
}

const submitSignatures = () => {
    form.post(route('tenant.contracts.save-signatures', props.contract.id), {
        onSuccess: () => {
            // Redirect after success
        },
    })
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const getPaymentMethod = (method) => {
    const methods = {
        card: 'Carte bancaire',
        bank_transfer: 'Virement bancaire',
        cash: 'Espèces',
        sepa: 'SEPA',
    }
    return methods[method] || method
}

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        pending_signature: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        expired: 'bg-red-100 text-red-800',
        terminated: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        pending_signature: 'En attente de signature',
        active: 'Actif',
        expired: 'Expiré',
        terminated: 'Résilié',
        cancelled: 'Annulé',
    }
    return labels[status] || status
}
</script>

<style scoped>
.prose {
    color: rgb(75, 85, 99);
}

.prose h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: rgb(17, 24, 39);
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.prose p {
    margin-bottom: 0.75rem;
}
</style>
