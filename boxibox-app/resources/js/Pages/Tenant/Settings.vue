<template>
    <TenantLayout title="Paramètres">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-slate-600 via-gray-600 to-slate-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                        <Cog6ToothIcon class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Paramètres</h1>
                        <p class="text-slate-200 mt-1">Gérez les paramètres de votre entreprise</p>
                    </div>
                </div>

                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="mt-6 bg-white/10 backdrop-blur-sm border border-emerald-400/30 rounded-xl p-4">
                    <div class="flex items-center">
                        <CheckCircleIcon class="w-5 h-5 text-emerald-300" />
                        <p class="ml-3 text-sm font-medium text-white">{{ $page.props.flash.success }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Tabs Navigation -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100">
                    <nav class="flex flex-wrap gap-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'bg-slate-600 text-white shadow-lg shadow-slate-200'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center space-x-2'
                            ]"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            <span>{{ tab.name }}</span>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- General Settings -->
            <div v-if="activeTab === 'general'" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <BuildingOfficeIcon class="w-5 h-5 text-slate-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Informations de l'entreprise</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveGeneral" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'entreprise</label>
                                <input type="text" v-model="generalForm.company_name" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="generalForm.errors.company_name" class="mt-2 text-sm text-red-600">{{ generalForm.errors.company_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" v-model="generalForm.email" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="generalForm.errors.email" class="mt-2 text-sm text-red-600">{{ generalForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                <input type="tel" v-model="generalForm.phone" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="generalForm.errors.phone" class="mt-2 text-sm text-red-600">{{ generalForm.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SIRET</label>
                                <input type="text" v-model="generalForm.siret" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="generalForm.errors.siret" class="mt-2 text-sm text-red-600">{{ generalForm.errors.siret }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                                <input type="text" v-model="generalForm.address" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="generalForm.errors.address" class="mt-2 text-sm text-red-600">{{ generalForm.errors.address }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                                <input type="text" v-model="generalForm.postal_code" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="generalForm.errors.postal_code" class="mt-2 text-sm text-red-600">{{ generalForm.errors.postal_code }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                                <input type="text" v-model="generalForm.city" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="generalForm.errors.city" class="mt-2 text-sm text-red-600">{{ generalForm.errors.city }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button
                                type="submit"
                                :disabled="generalForm.processing"
                                class="px-6 py-3 bg-gradient-to-r from-slate-600 to-gray-700 text-white font-semibold rounded-xl hover:from-slate-700 hover:to-gray-800 transition-all duration-200 shadow-lg shadow-slate-200 disabled:opacity-50 flex items-center space-x-2"
                            >
                                <ArrowPathIcon v-if="generalForm.processing" class="w-5 h-5 animate-spin" />
                                <CheckIcon v-else class="w-5 h-5" />
                                <span>{{ generalForm.processing ? 'Enregistrement...' : 'Enregistrer' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Billing Settings -->
            <div v-if="activeTab === 'billing'" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <DocumentTextIcon class="w-5 h-5 text-slate-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Paramètres de facturation</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveBilling" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Préfixe factures</label>
                                <input type="text" v-model="billingForm.invoice_prefix" placeholder="FAC-" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="billingForm.errors.invoice_prefix" class="mt-2 text-sm text-red-600">{{ billingForm.errors.invoice_prefix }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prochain numéro</label>
                                <input type="number" v-model="billingForm.next_invoice_number" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="billingForm.errors.next_invoice_number" class="mt-2 text-sm text-red-600">{{ billingForm.errors.next_invoice_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Taux de TVA (%)</label>
                                <input type="number" v-model="billingForm.default_tax_rate" step="0.1" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="billingForm.errors.default_tax_rate" class="mt-2 text-sm text-red-600">{{ billingForm.errors.default_tax_rate }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Délai de paiement (jours)</label>
                                <input type="number" v-model="billingForm.payment_due_days" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="billingForm.errors.payment_due_days" class="mt-2 text-sm text-red-600">{{ billingForm.errors.payment_due_days }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mentions légales facture</label>
                                <textarea v-model="billingForm.invoice_footer" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200"></textarea>
                                <p v-if="billingForm.errors.invoice_footer" class="mt-2 text-sm text-red-600">{{ billingForm.errors.invoice_footer }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button
                                type="submit"
                                :disabled="billingForm.processing"
                                class="px-6 py-3 bg-gradient-to-r from-slate-600 to-gray-700 text-white font-semibold rounded-xl hover:from-slate-700 hover:to-gray-800 transition-all duration-200 shadow-lg shadow-slate-200 disabled:opacity-50 flex items-center space-x-2"
                            >
                                <ArrowPathIcon v-if="billingForm.processing" class="w-5 h-5 animate-spin" />
                                <CheckIcon v-else class="w-5 h-5" />
                                <span>{{ billingForm.processing ? 'Enregistrement...' : 'Enregistrer' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notifications Settings -->
            <div v-if="activeTab === 'notifications'" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <BellIcon class="w-5 h-5 text-slate-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Paramètres de notifications</h2>
                </div>
                <div class="p-6">
                    <form @submit.prevent="saveNotifications">
                        <div class="space-y-2">
                            <div v-for="notification in notificationOptions" :key="notification.key" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                        <component :is="notification.icon" class="w-5 h-5 text-slate-600" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ notification.title }}</p>
                                        <p class="text-sm text-gray-500">{{ notification.description }}</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="notificationsForm[notification.key]" class="sr-only peer">
                                    <div class="w-12 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-600 shadow-inner"></div>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end pt-6 mt-6 border-t border-gray-100">
                            <button
                                type="submit"
                                :disabled="notificationsForm.processing"
                                class="px-6 py-3 bg-gradient-to-r from-slate-600 to-gray-700 text-white font-semibold rounded-xl hover:from-slate-700 hover:to-gray-800 transition-all duration-200 shadow-lg shadow-slate-200 disabled:opacity-50 flex items-center space-x-2"
                            >
                                <ArrowPathIcon v-if="notificationsForm.processing" class="w-5 h-5 animate-spin" />
                                <CheckIcon v-else class="w-5 h-5" />
                                <span>{{ notificationsForm.processing ? 'Enregistrement...' : 'Enregistrer' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SEPA Settings -->
            <div v-if="activeTab === 'sepa'" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <BanknotesIcon class="w-5 h-5 text-slate-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Paramètres SEPA</h2>
                </div>
                <div class="p-6">
                    <div class="mb-6 p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <div class="flex">
                            <InformationCircleIcon class="w-5 h-5 text-blue-600 flex-shrink-0" />
                            <p class="ml-3 text-sm text-blue-700">Ces informations sont utilisées pour générer les mandats de prélèvement SEPA.</p>
                        </div>
                    </div>
                    <form @submit.prevent="saveSepa" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ICS (Identifiant Créancier SEPA)</label>
                                <input type="text" v-model="sepaForm.ics" placeholder="FR00ZZZ000000" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200 font-mono" />
                                <p v-if="sepaForm.errors.ics" class="mt-2 text-sm text-red-600">{{ sepaForm.errors.ics }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom du créancier</label>
                                <input type="text" v-model="sepaForm.creditor_name" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200" />
                                <p v-if="sepaForm.errors.creditor_name" class="mt-2 text-sm text-red-600">{{ sepaForm.errors.creditor_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">IBAN</label>
                                <input type="text" v-model="sepaForm.iban" placeholder="FR76 0000 0000 0000 0000 0000 000" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200 font-mono" />
                                <p v-if="sepaForm.errors.iban" class="mt-2 text-sm text-red-600">{{ sepaForm.errors.iban }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">BIC</label>
                                <input type="text" v-model="sepaForm.bic" placeholder="BNPAFRPP" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 transition-all duration-200 font-mono" />
                                <p v-if="sepaForm.errors.bic" class="mt-2 text-sm text-red-600">{{ sepaForm.errors.bic }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end pt-4 border-t border-gray-100">
                            <button
                                type="submit"
                                :disabled="sepaForm.processing"
                                class="px-6 py-3 bg-gradient-to-r from-slate-600 to-gray-700 text-white font-semibold rounded-xl hover:from-slate-700 hover:to-gray-800 transition-all duration-200 shadow-lg shadow-slate-200 disabled:opacity-50 flex items-center space-x-2"
                            >
                                <ArrowPathIcon v-if="sepaForm.processing" class="w-5 h-5 animate-spin" />
                                <CheckIcon v-else class="w-5 h-5" />
                                <span>{{ sepaForm.processing ? 'Enregistrement...' : 'Enregistrer' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Settings -->
            <div v-if="activeTab === 'users'" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center">
                        <UsersIcon class="w-5 h-5 text-slate-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Utilisateurs</h2>
                    </div>
                    <button class="px-4 py-2 bg-gradient-to-r from-slate-600 to-gray-700 text-white font-semibold rounded-xl hover:from-slate-700 hover:to-gray-800 transition-all duration-200 shadow-lg shadow-slate-200 flex items-center space-x-2">
                        <PlusIcon class="w-5 h-5" />
                        <span>Ajouter un utilisateur</span>
                    </button>
                </div>
                <div class="divide-y divide-gray-100">
                    <div v-for="user in users" :key="user.id" class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-500 to-gray-600 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ user.name }}</p>
                                <p class="text-sm text-gray-500">{{ user.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span :class="[
                                'px-3 py-1 rounded-lg text-xs font-semibold border',
                                user.role === 'admin' ? 'bg-purple-100 text-purple-700 border-purple-200' :
                                user.role === 'manager' ? 'bg-blue-100 text-blue-700 border-blue-200' :
                                'bg-gray-100 text-gray-700 border-gray-200'
                            ]">
                                {{ getRoleLabel(user.role) }}
                            </span>
                            <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                                <EllipsisVerticalIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                    <div v-if="!users || users.length === 0" class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <UsersIcon class="w-8 h-8 text-gray-400" />
                        </div>
                        <p class="text-gray-500">Aucun utilisateur trouvé</p>
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
import {
    Cog6ToothIcon,
    CheckCircleIcon,
    BuildingOfficeIcon,
    DocumentTextIcon,
    BellIcon,
    BanknotesIcon,
    UsersIcon,
    PlusIcon,
    CheckIcon,
    ArrowPathIcon,
    InformationCircleIcon,
    EllipsisVerticalIcon,
    DocumentDuplicateIcon,
    CurrencyEuroIcon,
    ClockIcon,
    CalendarDaysIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline'

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
    { id: 'general', name: 'Général', icon: BuildingOfficeIcon },
    { id: 'billing', name: 'Facturation', icon: DocumentTextIcon },
    { id: 'notifications', name: 'Notifications', icon: BellIcon },
    { id: 'sepa', name: 'SEPA', icon: BanknotesIcon },
    { id: 'users', name: 'Utilisateurs', icon: UsersIcon },
]

const notificationOptions = [
    { key: 'new_contract', title: 'Nouveau contrat', description: 'Recevoir un email lors d\'un nouveau contrat', icon: DocumentDuplicateIcon },
    { key: 'payment_received', title: 'Paiement reçu', description: 'Recevoir un email lors d\'un paiement', icon: CurrencyEuroIcon },
    { key: 'overdue_invoice', title: 'Facture impayée', description: 'Recevoir une alerte pour les factures en retard', icon: ClockIcon },
    { key: 'contract_ending', title: 'Fin de contrat', description: 'Recevoir une alerte avant la fin d\'un contrat', icon: CalendarDaysIcon },
    { key: 'weekly_report', title: 'Rapport hebdomadaire', description: 'Recevoir un résumé hebdomadaire par email', icon: ChartBarIcon },
]

const getRoleLabel = (role) => {
    const labels = {
        admin: 'Administrateur',
        manager: 'Gestionnaire',
        user: 'Utilisateur',
    }
    return labels[role] || role
}

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
