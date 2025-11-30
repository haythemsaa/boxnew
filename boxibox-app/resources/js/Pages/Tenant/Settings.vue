<template>
    <TenantLayout title="Paramètres">
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Paramètres</h1>
                <p class="mt-1 text-sm text-gray-500">Gérez les paramètres de votre entreprise</p>
            </div>

            <!-- Success Message -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <p class="ml-3 text-sm font-medium text-green-800">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                        ]"
                    >
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- General Settings -->
            <div v-if="activeTab === 'general'" class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de l'entreprise</h3>
                <form @submit.prevent="saveGeneral" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom de l'entreprise</label>
                            <input type="text" v-model="generalForm.company_name" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="generalForm.errors.company_name" class="mt-1 text-sm text-red-600">{{ generalForm.errors.company_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" v-model="generalForm.email" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="generalForm.errors.email" class="mt-1 text-sm text-red-600">{{ generalForm.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <input type="tel" v-model="generalForm.phone" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="generalForm.errors.phone" class="mt-1 text-sm text-red-600">{{ generalForm.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">SIRET</label>
                            <input type="text" v-model="generalForm.siret" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="generalForm.errors.siret" class="mt-1 text-sm text-red-600">{{ generalForm.errors.siret }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Adresse</label>
                            <input type="text" v-model="generalForm.address" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="generalForm.errors.address" class="mt-1 text-sm text-red-600">{{ generalForm.errors.address }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Code postal</label>
                            <input type="text" v-model="generalForm.postal_code" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="generalForm.errors.postal_code" class="mt-1 text-sm text-red-600">{{ generalForm.errors.postal_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ville</label>
                            <input type="text" v-model="generalForm.city" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="generalForm.errors.city" class="mt-1 text-sm text-red-600">{{ generalForm.errors.city }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="generalForm.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            <span v-if="generalForm.processing">Enregistrement...</span>
                            <span v-else>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Billing Settings -->
            <div v-if="activeTab === 'billing'" class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres de facturation</h3>
                <form @submit.prevent="saveBilling" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Préfixe factures</label>
                            <input type="text" v-model="billingForm.invoice_prefix" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="FAC-" />
                            <p v-if="billingForm.errors.invoice_prefix" class="mt-1 text-sm text-red-600">{{ billingForm.errors.invoice_prefix }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Prochain numéro</label>
                            <input type="number" v-model="billingForm.next_invoice_number" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="billingForm.errors.next_invoice_number" class="mt-1 text-sm text-red-600">{{ billingForm.errors.next_invoice_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Taux de TVA (%)</label>
                            <input type="number" v-model="billingForm.default_tax_rate" step="0.1" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="billingForm.errors.default_tax_rate" class="mt-1 text-sm text-red-600">{{ billingForm.errors.default_tax_rate }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Délai de paiement (jours)</label>
                            <input type="number" v-model="billingForm.payment_due_days" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="billingForm.errors.payment_due_days" class="mt-1 text-sm text-red-600">{{ billingForm.errors.payment_due_days }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Mentions légales facture</label>
                            <textarea v-model="billingForm.invoice_footer" rows="3" class="mt-1 block w-full rounded-lg border-gray-300"></textarea>
                            <p v-if="billingForm.errors.invoice_footer" class="mt-1 text-sm text-red-600">{{ billingForm.errors.invoice_footer }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="billingForm.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            <span v-if="billingForm.processing">Enregistrement...</span>
                            <span v-else>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notifications Settings -->
            <div v-if="activeTab === 'notifications'" class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres de notifications</h3>
                <form @submit.prevent="saveNotifications">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b">
                            <div>
                                <p class="font-medium text-gray-900">Nouveau contrat</p>
                                <p class="text-sm text-gray-500">Recevoir un email lors d'un nouveau contrat</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="notificationsForm.new_contract" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b">
                            <div>
                                <p class="font-medium text-gray-900">Paiement reçu</p>
                                <p class="text-sm text-gray-500">Recevoir un email lors d'un paiement</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="notificationsForm.payment_received" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b">
                            <div>
                                <p class="font-medium text-gray-900">Facture impayée</p>
                                <p class="text-sm text-gray-500">Recevoir une alerte pour les factures en retard</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="notificationsForm.overdue_invoice" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b">
                            <div>
                                <p class="font-medium text-gray-900">Fin de contrat</p>
                                <p class="text-sm text-gray-500">Recevoir une alerte avant la fin d'un contrat</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="notificationsForm.contract_ending" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="font-medium text-gray-900">Rapport hebdomadaire</p>
                                <p class="text-sm text-gray-500">Recevoir un résumé hebdomadaire par email</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="notificationsForm.weekly_report" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6">
                        <button
                            type="submit"
                            :disabled="notificationsForm.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            <span v-if="notificationsForm.processing">Enregistrement...</span>
                            <span v-else>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- SEPA Settings -->
            <div v-if="activeTab === 'sepa'" class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres SEPA</h3>
                <form @submit.prevent="saveSepa" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ICS (Identifiant Créancier SEPA)</label>
                            <input type="text" v-model="sepaForm.ics" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="FR00ZZZ000000" />
                            <p v-if="sepaForm.errors.ics" class="mt-1 text-sm text-red-600">{{ sepaForm.errors.ics }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">IBAN</label>
                            <input type="text" v-model="sepaForm.iban" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="FR76 0000 0000 0000 0000 0000 000" />
                            <p v-if="sepaForm.errors.iban" class="mt-1 text-sm text-red-600">{{ sepaForm.errors.iban }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">BIC</label>
                            <input type="text" v-model="sepaForm.bic" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="BNPAFRPP" />
                            <p v-if="sepaForm.errors.bic" class="mt-1 text-sm text-red-600">{{ sepaForm.errors.bic }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom du créancier</label>
                            <input type="text" v-model="sepaForm.creditor_name" class="mt-1 block w-full rounded-lg border-gray-300" />
                            <p v-if="sepaForm.errors.creditor_name" class="mt-1 text-sm text-red-600">{{ sepaForm.errors.creditor_name }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="sepaForm.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            <span v-if="sepaForm.processing">Enregistrement...</span>
                            <span v-else>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Users Settings -->
            <div v-if="activeTab === 'users'" class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Utilisateurs</h3>
                        <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            Ajouter un utilisateur
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    <div v-for="user in users" :key="user.id" class="p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold">
                                {{ user.name.charAt(0) }}
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                <p class="text-sm text-gray-500">{{ user.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span :class="user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ user.role }}
                            </span>
                            <button class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    tenant: Object,
    users: Array,
    settings: Object,
    billing: Object,
    notifications: Object,
    sepa: Object,
})

const activeTab = ref('general')

const tabs = [
    { id: 'general', name: 'Général' },
    { id: 'billing', name: 'Facturation' },
    { id: 'notifications', name: 'Notifications' },
    { id: 'sepa', name: 'SEPA' },
    { id: 'users', name: 'Utilisateurs' },
]

// Forms with Inertia
const generalForm = useForm({
    company_name: props.settings?.company_name || '',
    email: props.settings?.email || '',
    phone: props.settings?.phone || '',
    siret: props.settings?.siret || '',
    address: props.settings?.address || '',
    postal_code: props.settings?.postal_code || '',
    city: props.settings?.city || '',
})

const billingForm = useForm({
    invoice_prefix: props.billing?.invoice_prefix || 'FAC-',
    next_invoice_number: props.billing?.next_invoice_number || 1,
    default_tax_rate: props.billing?.default_tax_rate || 20,
    payment_due_days: props.billing?.payment_due_days || 30,
    invoice_footer: props.billing?.invoice_footer || '',
})

const notificationsForm = useForm({
    new_contract: props.notifications?.new_contract ?? true,
    payment_received: props.notifications?.payment_received ?? true,
    overdue_invoice: props.notifications?.overdue_invoice ?? true,
    contract_ending: props.notifications?.contract_ending ?? true,
    weekly_report: props.notifications?.weekly_report ?? false,
})

const sepaForm = useForm({
    ics: props.sepa?.ics || '',
    iban: props.sepa?.iban || '',
    bic: props.sepa?.bic || '',
    creditor_name: props.sepa?.creditor_name || '',
})

const saveGeneral = () => {
    generalForm.post(route('tenant.settings.update-general'), {
        preserveScroll: true,
    })
}

const saveBilling = () => {
    billingForm.post(route('tenant.settings.update-billing'), {
        preserveScroll: true,
    })
}

const saveNotifications = () => {
    notificationsForm.post(route('tenant.settings.update-notifications'), {
        preserveScroll: true,
    })
}

const saveSepa = () => {
    sepaForm.post(route('tenant.settings.update-sepa'), {
        preserveScroll: true,
    })
}
</script>
