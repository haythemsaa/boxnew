<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue'

const props = defineProps({
    contracts: Array,
    paymentMethods: Array,
})

const selectedContract = ref(null)
const showMethodModal = ref(false)

const toggleAutopay = (contract) => {
    const form = useForm({
        enabled: !contract.auto_pay,
        payment_method_id: props.paymentMethods.find(m => m.is_default)?.id || null,
    })

    form.post(route('customer.portal.autopay.toggle', contract.id), {
        preserveScroll: true,
    })
}

const hasDefaultMethod = computed(() => props.paymentMethods.some(m => m.is_default))

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price)
}
</script>

<template>
    <Head title="Paiement automatique" />

    <CustomerPortalLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Paiement automatique</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Activez le prélèvement automatique pour ne plus jamais oublier un paiement
                    </p>
                </div>

                <!-- Benefits Banner -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-6 mb-8 text-white">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold mb-2">Avantages de l'Autopay</h2>
                            <ul class="space-y-1 text-green-100">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Plus de retards de paiement
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Évitez les frais de retard
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Email de confirmation à chaque prélèvement
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Désactivable à tout moment
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- No Payment Method Warning -->
                <div v-if="paymentMethods.length === 0" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-6">
                    <div class="flex gap-3">
                        <svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="font-medium text-amber-800 dark:text-amber-300">Aucun moyen de paiement enregistré</h3>
                            <p class="text-sm text-amber-700 dark:text-amber-400 mt-1">
                                Vous devez d'abord ajouter une carte bancaire ou un mandat SEPA pour activer le paiement automatique.
                            </p>
                            <a href="#" class="inline-flex items-center gap-2 mt-3 text-sm font-medium text-amber-700 dark:text-amber-300 hover:underline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Ajouter un moyen de paiement
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Saved Payment Methods -->
                <div v-if="paymentMethods.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Moyen de paiement utilisé</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl">
                                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ paymentMethods.find(m => m.is_default)?.label || paymentMethods[0]?.label }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Expire {{ paymentMethods.find(m => m.is_default)?.exp_month || paymentMethods[0]?.exp_month }}/{{ paymentMethods.find(m => m.is_default)?.exp_year || paymentMethods[0]?.exp_year }}
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm rounded-full">
                                Par défaut
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contracts List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Vos contrats</h2>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="contract in contracts" :key="contract.id" class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ contract.site_name }} - Box {{ contract.box_name }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                                        <p>Contrat N° {{ contract.contract_number }}</p>
                                        <p>Montant: <span class="font-medium text-gray-900 dark:text-white">{{ formatPrice(contract.monthly_price) }}/mois</span></p>
                                        <p>Prochain prélèvement: <span class="font-medium">{{ contract.next_billing }}</span></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <!-- Status Badge -->
                                    <span v-if="contract.auto_pay" class="flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm font-medium">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Activé
                                    </span>
                                    <span v-else class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-sm font-medium">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        Désactivé
                                    </span>

                                    <!-- Toggle Button -->
                                    <button
                                        @click="toggleAutopay(contract)"
                                        :disabled="!hasDefaultMethod && !contract.auto_pay"
                                        :class="[
                                            'relative inline-flex h-8 w-14 items-center rounded-full transition-colors duration-200',
                                            contract.auto_pay
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 dark:bg-gray-600',
                                            (!hasDefaultMethod && !contract.auto_pay) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition-transform duration-200',
                                                contract.auto_pay ? 'translate-x-7' : 'translate-x-1'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="contracts.length === 0" class="p-12 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucun contrat actif</h3>
                            <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas de contrat actif pour le moment.</p>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="mt-8 bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Questions fréquentes</h3>
                    <div class="space-y-4 text-sm">
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300">Quand sera prélevé mon compte ?</p>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Le prélèvement est effectué automatiquement le jour de facturation indiqué sur votre contrat.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300">Puis-je annuler à tout moment ?</p>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Oui, vous pouvez désactiver le paiement automatique à tout moment depuis cette page.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700 dark:text-gray-300">Que se passe-t-il si le prélèvement échoue ?</p>
                            <p class="text-gray-500 dark:text-gray-400 mt-1">Vous serez notifié par email et une nouvelle tentative sera effectuée sous 48h.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>
