<template>
    <TenantLayout title="Nouveau Paiement">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50 to-teal-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.payments.index')"
                        class="inline-flex items-center text-sm text-emerald-600 hover:text-emerald-800 transition-colors mb-4"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux paiements
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouveau Paiement</h1>
                            <p class="text-gray-500 mt-1">Enregistrez une nouvelle transaction de paiement</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div v-for="step in steps" :key="step.number" class="flex items-center" :class="step.number < steps.length ? 'flex-1' : ''">
                            <div class="flex flex-col items-center">
                                <div :class="[
                                    'w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg transition-all duration-300',
                                    currentStep === step.number
                                        ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/25'
                                        : currentStep > step.number
                                            ? 'bg-emerald-500 text-white'
                                            : 'bg-gray-100 text-gray-400'
                                ]">
                                    <svg v-if="currentStep > step.number" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span v-else>{{ step.number }}</span>
                                </div>
                                <span :class="[
                                    'mt-2 text-sm font-medium transition-colors',
                                    currentStep >= step.number ? 'text-emerald-600' : 'text-gray-400'
                                ]">{{ step.title }}</span>
                            </div>
                            <div v-if="step.number < steps.length" :class="[
                                'flex-1 h-1 mx-4 rounded-full transition-colors',
                                currentStep > step.number ? 'bg-emerald-500' : 'bg-gray-200'
                            ]"></div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <form @submit.prevent="submit">
                        <!-- Step 1: Client & Facture -->
                        <div v-show="currentStep === 1" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Client & Facture</h3>
                                    <p class="text-sm text-gray-500">Sélectionnez le client et la facture associée</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Client -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Client <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.customer_id"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all"
                                        :class="{ 'border-red-300': form.errors.customer_id }"
                                    >
                                        <option value="">Sélectionner un client</option>
                                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                            {{ getCustomerName(customer) }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.customer_id" class="mt-2 text-sm text-red-600">{{ form.errors.customer_id }}</p>
                                </div>

                                <!-- Facture associée -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Facture associée (optionnel)
                                    </label>
                                    <select
                                        v-model="form.invoice_id"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all"
                                        :disabled="!form.customer_id"
                                    >
                                        <option value="">Aucune facture</option>
                                        <option v-for="invoice in filteredInvoices" :key="invoice.id" :value="invoice.id">
                                            {{ invoice.invoice_number }} - Reste: {{ (invoice.total - invoice.paid_amount).toFixed(2) }} €
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Sélectionnez d'abord un client pour voir ses factures</p>
                                </div>

                                <!-- Type de paiement -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Type de transaction <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <label
                                            v-for="type in paymentTypes"
                                            :key="type.value"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.type"
                                                :value="type.value"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-4 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-emerald-500 peer-checked:bg-emerald-50',
                                                'hover:border-emerald-300 hover:bg-emerald-50/50',
                                                form.type !== type.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="['w-10 h-10 rounded-full mx-auto mb-2 flex items-center justify-center', type.bgColor]">
                                                    <svg class="w-5 h-5" :class="type.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900 text-sm">{{ type.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Numéro de paiement -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Numéro de paiement
                                    </label>
                                    <input
                                        v-model="form.payment_number"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all"
                                        placeholder="Laisser vide pour générer automatiquement"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Un numéro sera généré automatiquement si laissé vide</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Montant & Méthode -->
                        <div v-show="currentStep === 2" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Montant & Méthode</h3>
                                    <p class="text-sm text-gray-500">Définissez le montant et le mode de paiement</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Montant -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Montant <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium">€</span>
                                        </div>
                                        <input
                                            v-model.number="form.amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 transition-all text-2xl font-bold"
                                            :class="{ 'border-red-300': form.errors.amount }"
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <p v-if="form.errors.amount" class="mt-2 text-sm text-red-600">{{ form.errors.amount }}</p>
                                </div>

                                <!-- Méthode de paiement -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Méthode de paiement <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-4 gap-3">
                                        <label
                                            v-for="method in paymentMethods"
                                            :key="method.value"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.method"
                                                :value="method.value"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-3 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-teal-500 peer-checked:bg-teal-50',
                                                'hover:border-teal-300 hover:bg-teal-50/50',
                                                form.method !== method.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="['w-8 h-8 rounded-lg mx-auto mb-1 flex items-center justify-center', method.bgColor]">
                                                    <svg class="w-4 h-4" :class="method.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="method.icon" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900 text-xs">{{ method.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Passerelle -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Passerelle de paiement
                                        </label>
                                        <select
                                            v-model="form.gateway"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 transition-all"
                                        >
                                            <option value="stripe">Stripe</option>
                                            <option value="paypal">PayPal</option>
                                            <option value="sepa">SEPA</option>
                                            <option value="manual">Manuel</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Date de paiement
                                        </label>
                                        <input
                                            v-model="form.paid_at"
                                            type="date"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 transition-all"
                                        />
                                    </div>
                                </div>

                                <!-- Statut -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Statut <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-4 gap-3">
                                        <label
                                            v-for="status in paymentStatuses"
                                            :key="status.value"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.status"
                                                :value="status.value"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-3 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-teal-500 peer-checked:bg-teal-50',
                                                'hover:border-teal-300 hover:bg-teal-50/50',
                                                form.status !== status.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="['w-3 h-3 rounded-full mx-auto mb-2', status.dotColor]"></div>
                                                <span class="font-medium text-gray-900 text-xs">{{ status.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Récapitulatif -->
                        <div v-show="currentStep === 3" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Récapitulatif & Notes</h3>
                                    <p class="text-sm text-gray-500">Vérifiez les informations et ajoutez des notes</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Notes (optionnel)
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    maxlength="2000"
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all resize-none"
                                    placeholder="Ajoutez des notes ou commentaires..."
                                ></textarea>
                            </div>

                            <!-- Récapitulatif -->
                            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-6">
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Récapitulatif du paiement
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Client -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Client</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p class="font-medium">{{ selectedCustomerName }}</p>
                                            <p v-if="selectedInvoice" class="text-gray-500">
                                                Facture: {{ selectedInvoice.invoice_number }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Montant -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Montant</span>
                                        </div>
                                        <p class="text-2xl font-bold text-emerald-600">{{ form.amount || 0 }} €</p>
                                    </div>

                                    <!-- Méthode -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Paiement</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p><span class="text-gray-500">Méthode:</span> <span class="font-medium">{{ selectedMethodLabel }}</span></p>
                                            <p><span class="text-gray-500">Passerelle:</span> <span class="font-medium">{{ form.gateway }}</span></p>
                                        </div>
                                    </div>

                                    <!-- Statut -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Détails</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p>
                                                <span class="text-gray-500">Type:</span>
                                                <span :class="typeColor" class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium">
                                                    {{ selectedTypeLabel }}
                                                </span>
                                            </p>
                                            <p>
                                                <span class="text-gray-500">Statut:</span>
                                                <span :class="statusColor" class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium">
                                                    {{ selectedStatusLabel }}
                                                </span>
                                            </p>
                                            <p><span class="text-gray-500">Date:</span> {{ form.paid_at || 'Aujourd\'hui' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="inline-flex items-center px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-100 transition-all"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Précédent
                            </button>
                            <div v-else></div>

                            <div class="flex items-center space-x-3">
                                <Link
                                    :href="route('tenant.payments.index')"
                                    class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-medium transition-colors"
                                >
                                    Annuler
                                </Link>
                                <button
                                    v-if="currentStep < 3"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Suivant
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <button
                                    v-else
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="form.processing" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer le paiement' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    customers: Array,
    invoices: Array,
    contracts: Array,
})

const currentStep = ref(1)

const steps = [
    { number: 1, title: 'Client' },
    { number: 2, title: 'Montant' },
    { number: 3, title: 'Récapitulatif' }
]

const paymentTypes = [
    { value: 'payment', label: 'Paiement', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', bgColor: 'bg-emerald-100', iconColor: 'text-emerald-600' },
    { value: 'refund', label: 'Remboursement', icon: 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6', bgColor: 'bg-red-100', iconColor: 'text-red-600' },
    { value: 'deposit', label: 'Caution', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' }
]

const paymentMethods = [
    { value: 'card', label: 'Carte', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
    { value: 'bank_transfer', label: 'Virement', icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z', bgColor: 'bg-indigo-100', iconColor: 'text-indigo-600' },
    { value: 'cash', label: 'Espèces', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', bgColor: 'bg-green-100', iconColor: 'text-green-600' },
    { value: 'cheque', label: 'Chèque', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', bgColor: 'bg-amber-100', iconColor: 'text-amber-600' },
    { value: 'sepa', label: 'SEPA', icon: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3', bgColor: 'bg-purple-100', iconColor: 'text-purple-600' },
    { value: 'stripe', label: 'Stripe', icon: 'M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z', bgColor: 'bg-violet-100', iconColor: 'text-violet-600' },
    { value: 'paypal', label: 'PayPal', icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', bgColor: 'bg-sky-100', iconColor: 'text-sky-600' }
]

const paymentStatuses = [
    { value: 'pending', label: 'En attente', dotColor: 'bg-amber-500' },
    { value: 'processing', label: 'En cours', dotColor: 'bg-blue-500' },
    { value: 'completed', label: 'Terminé', dotColor: 'bg-emerald-500' },
    { value: 'failed', label: 'Échoué', dotColor: 'bg-red-500' }
]

const form = useForm({
    customer_id: '',
    invoice_id: '',
    contract_id: '',
    payment_number: '',
    type: 'payment',
    status: 'completed',
    amount: '',
    fee: 0,
    currency: 'EUR',
    method: 'card',
    gateway: 'stripe',
    gateway_payment_id: '',
    card_brand: '',
    card_last_four: '',
    paid_at: new Date().toISOString().split('T')[0],
    notes: '',
})

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        return form.customer_id && form.type
    }
    if (currentStep.value === 2) {
        return form.amount && form.method && form.status
    }
    return true
})

const filteredInvoices = computed(() => {
    if (!form.customer_id || !props.invoices) return props.invoices || []
    return props.invoices.filter((invoice) => invoice.customer_id == form.customer_id)
})

const getCustomerName = (customer) => {
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const selectedCustomerName = computed(() => {
    if (!form.customer_id || !props.customers) return '-'
    const customer = props.customers.find(c => c.id == form.customer_id)
    return customer ? getCustomerName(customer) : '-'
})

const selectedInvoice = computed(() => {
    if (!form.invoice_id || !props.invoices) return null
    return props.invoices.find(i => i.id == form.invoice_id)
})

const selectedTypeLabel = computed(() => {
    const type = paymentTypes.find(t => t.value === form.type)
    return type ? type.label : form.type
})

const selectedMethodLabel = computed(() => {
    const method = paymentMethods.find(m => m.value === form.method)
    return method ? method.label : form.method
})

const selectedStatusLabel = computed(() => {
    const status = paymentStatuses.find(s => s.value === form.status)
    return status ? status.label : form.status
})

const typeColor = computed(() => {
    switch (form.type) {
        case 'payment': return 'bg-emerald-100 text-emerald-700'
        case 'refund': return 'bg-red-100 text-red-700'
        case 'deposit': return 'bg-blue-100 text-blue-700'
        default: return 'bg-gray-100 text-gray-700'
    }
})

const statusColor = computed(() => {
    switch (form.status) {
        case 'completed': return 'bg-emerald-100 text-emerald-700'
        case 'pending': return 'bg-amber-100 text-amber-700'
        case 'processing': return 'bg-blue-100 text-blue-700'
        case 'failed': return 'bg-red-100 text-red-700'
        default: return 'bg-gray-100 text-gray-700'
    }
})

const nextStep = () => {
    if (currentStep.value < 3 && canProceed.value) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const submit = () => {
    form.post(route('tenant.payments.store'))
}
</script>
