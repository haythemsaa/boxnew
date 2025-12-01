<template>
    <TenantLayout title="Créer un contrat">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 sticky top-16 z-40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Créer un contrat</h1>
                            <p class="mt-2 text-sm text-gray-600">Remplissez le formulaire en suivant les étapes</p>
                        </div>
                        <Link
                            :href="route('tenant.contracts.index')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            ← Retour
                        </Link>
                    </div>

                    <!-- Progress Steps -->
                    <div class="flex items-center justify-between">
                        <div v-for="(step, index) in steps" :key="index" class="flex-1 relative">
                            <!-- Step Button -->
                            <button
                                @click="currentStep = index"
                                :disabled="index > currentStep && !isStepValid(index - 1)"
                                class="relative w-full flex flex-col items-center group disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <!-- Step Circle -->
                                <div
                                    :class="[
                                        'flex items-center justify-center w-12 h-12 rounded-full font-bold text-sm transition-all duration-300 mx-auto mb-2',
                                        index < currentStep
                                            ? 'bg-green-500 text-white'
                                            : index === currentStep
                                            ? 'bg-primary-600 text-white ring-4 ring-primary-200'
                                            : 'bg-gray-200 text-gray-600 group-hover:bg-gray-300'
                                    ]"
                                >
                                    <CheckIcon v-if="index < currentStep" class="h-5 w-5" />
                                    <span v-else>{{ index + 1 }}</span>
                                </div>

                                <!-- Step Label -->
                                <span
                                    :class="[
                                        'text-xs font-medium text-center whitespace-nowrap',
                                        index <= currentStep ? 'text-gray-900' : 'text-gray-600'
                                    ]"
                                >
                                    {{ step.name }}
                                </span>
                            </button>

                            <!-- Connector Line -->
                            <div
                                v-if="index < steps.length - 1"
                                :class="[
                                    'absolute top-6 left-1/2 w-full h-1 -translate-y-1/2 transition-all duration-300',
                                    index < currentStep ? 'bg-green-500' : 'bg-gray-200'
                                ]"
                                style="left: calc(50% + 24px); right: -50%;"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <form @submit.prevent="submitForm">
                    <!-- Step 1: BOX -->
                    <transition name="slide-fade" mode="out-in">
                        <div v-if="currentStep === 0" key="step-0">
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-8 py-8 text-white">
                                    <h2 class="text-2xl font-bold mb-2">{{ steps[0].name }}</h2>
                                    <p class="text-blue-100">Sélectionnez un box et un site</p>
                                </div>

                                <div class="p-8 space-y-6">
                                    <!-- Site Selection -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 mb-3">
                                            <span class="flex items-center gap-2">
                                                <BuildingOffice2Icon class="h-4 w-4 text-blue-500" />
                                                Site
                                            </span>
                                        </label>
                                        <select
                                            v-model="form.site_id"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Sélectionnez un site</option>
                                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                                {{ site.name }} ({{ site.city }})
                                            </option>
                                        </select>
                                        <p v-if="form.errors.site_id" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.site_id }}
                                        </p>
                                    </div>

                                    <!-- Box Selection -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 mb-3">
                                            <span class="flex items-center gap-2">
                                                <ArchiveBoxIcon class="h-4 w-4 text-blue-500" />
                                                Box
                                            </span>
                                        </label>
                                        <select
                                            v-model="form.box_id"
                                            @change="updateMonthlyPrice"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        >
                                            <option value="">Sélectionnez un box</option>
                                            <option v-for="box in filteredBoxes" :key="box.id" :value="box.id">
                                                {{ getBoxLabel(box) }} - {{ box.volume }}m³ - {{ box.base_price }}€/mois
                                            </option>
                                        </select>
                                        <p v-if="form.errors.box_id" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.box_id }}
                                        </p>
                                    </div>

                                    <!-- Box Preview -->
                                    <div v-if="selectedBox" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                        <h3 class="font-semibold text-gray-900 mb-4">Aperçu du box</h3>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                            <div>
                                                <p class="text-xs text-gray-600">Numéro</p>
                                                <p class="font-semibold text-gray-900">{{ selectedBox.number }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Volume</p>
                                                <p class="font-semibold text-gray-900">{{ selectedBox.volume }}m³</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Prix de base</p>
                                                <p class="font-semibold text-gray-900">{{ selectedBox.base_price }}€</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Dimensions</p>
                                                <p class="text-sm text-gray-900">
                                                    {{ selectedBox.length }}m × {{ selectedBox.width }}m × {{ selectedBox.height }}m
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Étage</p>
                                                <p class="font-semibold text-gray-900">
                                                    {{ selectedBox.floor?.name || 'N/A' }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Statut</p>
                                                <span class="inline-block px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Disponible
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Step 2: CLIENT -->
                    <transition name="slide-fade" mode="out-in">
                        <div v-if="currentStep === 1" key="step-1">
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-8 py-8 text-white">
                                    <h2 class="text-2xl font-bold mb-2">{{ steps[1].name }}</h2>
                                    <p class="text-purple-100">Sélectionnez ou créez un client</p>
                                </div>

                                <div class="p-8 space-y-6">
                                    <!-- Customer Selection -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-900 mb-3">
                                            <span class="flex items-center gap-2">
                                                <UserIcon class="h-4 w-4 text-purple-500" />
                                                Client
                                            </span>
                                        </label>
                                        <select
                                            v-model="form.customer_id"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        >
                                            <option value="">Sélectionnez un client</option>
                                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                                {{ getCustomerName(customer) }} ({{ customer.email }})
                                            </option>
                                        </select>
                                        <p v-if="form.errors.customer_id" class="mt-2 text-sm text-red-600">
                                            {{ form.errors.customer_id }}
                                        </p>
                                        <Link
                                            :href="route('tenant.customers.create')"
                                            class="mt-3 inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium"
                                        >
                                            <PlusIcon class="h-4 w-4 mr-1" />
                                            Créer un nouveau client
                                        </Link>
                                    </div>

                                    <!-- Customer Preview -->
                                    <div v-if="selectedCustomer" class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                                        <h3 class="font-semibold text-gray-900 mb-4">Détails du client</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-xs text-gray-600">Nom</p>
                                                <p class="font-semibold text-gray-900">{{ getCustomerName(selectedCustomer) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Type</p>
                                                <p class="font-semibold text-gray-900">
                                                    {{ selectedCustomer.type === 'company' ? 'Entreprise' : 'Particulier' }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Email</p>
                                                <p class="text-sm text-gray-900">{{ selectedCustomer.email }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Téléphone</p>
                                                <p class="text-sm text-gray-900">{{ selectedCustomer.phone || 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Contrats actifs</p>
                                                <p class="font-semibold text-gray-900">{{ selectedCustomer.total_contracts || 0 }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-600">Solde</p>
                                                <p class="font-semibold" :class="selectedCustomer.outstanding_balance > 0 ? 'text-red-600' : 'text-green-600'">
                                                    {{ selectedCustomer.outstanding_balance }}€
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Step 3: CRÉATION -->
                    <transition name="slide-fade" mode="out-in">
                        <div v-if="currentStep === 2" key="step-2">
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-green-500 to-green-600 px-8 py-8 text-white">
                                    <h2 class="text-2xl font-bold mb-2">{{ steps[2].name }}</h2>
                                    <p class="text-green-100">Configurez les termes du contrat</p>
                                </div>

                                <div class="p-8 space-y-8">
                                    <!-- Contract Details -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                            <DocumentTextIcon class="h-5 w-5 text-green-500" />
                                            Détails du contrat
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Numéro du contrat</label>
                                                <input
                                                    v-model="form.contract_number"
                                                    type="text"
                                                    placeholder="Auto-généré"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                                                <select
                                                    v-model="form.status"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                >
                                                    <option value="draft">Brouillon</option>
                                                    <option value="pending_signature">En attente de signature</option>
                                                    <option value="active">Actif</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Type de contrat</label>
                                                <select
                                                    v-model="form.type"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                >
                                                    <option value="standard">Standard</option>
                                                    <option value="short_term">Court terme</option>
                                                    <option value="long_term">Long terme</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dates Section -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                            <CalendarIcon class="h-5 w-5 text-green-500" />
                                            Dates
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                                                <input
                                                    v-model="form.start_date"
                                                    type="date"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                                                    {{ form.errors.start_date }}
                                                </p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                                                <input
                                                    v-model="form.end_date"
                                                    type="date"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                                                    {{ form.errors.end_date }}
                                                </p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Préavis de résiliation (jours)
                                                </label>
                                                <input
                                                    v-model.number="form.notice_period_days"
                                                    type="number"
                                                    min="0"
                                                    max="365"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Renouvellement automatique</label>
                                                <div class="flex items-center h-10">
                                                    <input
                                                        v-model="form.auto_renew"
                                                        type="checkbox"
                                                        class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-500"
                                                    />
                                                    <label class="ml-2 text-sm text-gray-700">
                                                        Renouveler automatiquement
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pricing Section -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                            <CurrencyEuroIcon class="h-5 w-5 text-green-500" />
                                            Tarification
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Prix mensuel (€)
                                                </label>
                                                <input
                                                    v-model.number="form.monthly_price"
                                                    type="number"
                                                    step="0.01"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Dépôt de garantie (€)
                                                </label>
                                                <input
                                                    v-model.number="form.deposit_amount"
                                                    type="number"
                                                    step="0.01"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Remise (%)
                                                </label>
                                                <input
                                                    v-model.number="form.discount_percentage"
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Montant remise fixe (€)
                                                </label>
                                                <input
                                                    v-model.number="form.discount_amount"
                                                    type="number"
                                                    step="0.01"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Billing Section -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                            <ReceiptPercentIcon class="h-5 w-5 text-green-500" />
                                            Facturation
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Fréquence de facturation
                                                </label>
                                                <select
                                                    v-model="form.billing_frequency"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                >
                                                    <option value="monthly">Mensuel</option>
                                                    <option value="quarterly">Trimestriel</option>
                                                    <option value="yearly">Annuel</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Jour de facturation
                                                </label>
                                                <input
                                                    v-model.number="form.billing_day"
                                                    type="number"
                                                    min="1"
                                                    max="31"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Méthode de paiement
                                                </label>
                                                <select
                                                    v-model="form.payment_method"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                >
                                                    <option value="card">Carte bancaire</option>
                                                    <option value="bank_transfer">Virement bancaire</option>
                                                    <option value="cash">Espèces</option>
                                                    <option value="sepa">SEPA</option>
                                                </select>
                                            </div>
                                            <div class="flex items-center h-10">
                                                <input
                                                    v-model="form.auto_pay"
                                                    type="checkbox"
                                                    class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-500"
                                                />
                                                <label class="ml-2 text-sm text-gray-700">
                                                    Paiement automatique
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Access & Keys Section -->
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                            <KeyIcon class="h-5 w-5 text-green-500" />
                                            Accès et clés
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Code d'accès
                                                </label>
                                                <input
                                                    v-model="form.access_code"
                                                    type="text"
                                                    maxlength="10"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                />
                                            </div>
                                            <div></div>
                                            <div class="flex items-center h-10">
                                                <input
                                                    v-model="form.key_given"
                                                    type="checkbox"
                                                    class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-500"
                                                />
                                                <label class="ml-2 text-sm text-gray-700">
                                                    Clé remise
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Step 4: VALIDATION -->
                    <transition name="slide-fade" mode="out-in">
                        <div v-if="currentStep === 3" key="step-3">
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                                <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-8 py-8 text-white">
                                    <h2 class="text-2xl font-bold mb-2">{{ steps[3].name }}</h2>
                                    <p class="text-amber-100">Vérifiez les informations avant de confirmer</p>
                                </div>

                                <div class="p-8 space-y-8">
                                    <!-- Summary Cards -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Box Summary -->
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <ArchiveBoxIcon class="h-5 w-5 text-blue-600" />
                                                Box
                                            </h3>
                                            <div v-if="selectedBox" class="space-y-2 text-sm">
                                                <p><span class="text-gray-600">Numéro:</span> <strong>{{ selectedBox.number }}</strong></p>
                                                <p><span class="text-gray-600">Volume:</span> <strong>{{ selectedBox.volume }}m³</strong></p>
                                                <p><span class="text-gray-600">Prix:</span> <strong>{{ form.monthly_price }}€/mois</strong></p>
                                            </div>
                                        </div>

                                        <!-- Customer Summary -->
                                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <UserIcon class="h-5 w-5 text-purple-600" />
                                                Client
                                            </h3>
                                            <div v-if="selectedCustomer" class="space-y-2 text-sm">
                                                <p><span class="text-gray-600">Nom:</span> <strong>{{ getCustomerName(selectedCustomer) }}</strong></p>
                                                <p><span class="text-gray-600">Email:</span> <strong>{{ selectedCustomer.email }}</strong></p>
                                                <p><span class="text-gray-600">Type:</span> <strong>{{ selectedCustomer.type === 'company' ? 'Entreprise' : 'Particulier' }}</strong></p>
                                            </div>
                                        </div>

                                        <!-- Contract Period -->
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <CalendarIcon class="h-5 w-5 text-green-600" />
                                                Période
                                            </h3>
                                            <div class="space-y-2 text-sm">
                                                <p><span class="text-gray-600">Du:</span> <strong>{{ form.start_date }}</strong></p>
                                                <p><span class="text-gray-600">Au:</span> <strong>{{ form.end_date }}</strong></p>
                                                <p><span class="text-gray-600">Préavis:</span> <strong>{{ form.notice_period_days }} jours</strong></p>
                                            </div>
                                        </div>

                                        <!-- Pricing Summary -->
                                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                                <CurrencyEuroIcon class="h-5 w-5 text-amber-600" />
                                                Tarification
                                            </h3>
                                            <div class="space-y-2 text-sm">
                                                <p><span class="text-gray-600">Prix mensuel:</span> <strong>{{ form.monthly_price }}€</strong></p>
                                                <p v-if="form.deposit_amount"><span class="text-gray-600">Dépôt:</span> <strong>{{ form.deposit_amount }}€</strong></p>
                                                <p v-if="form.discount_percentage"><span class="text-gray-600">Remise:</span> <strong>-{{ form.discount_percentage }}%</strong></p>
                                                <p v-if="form.discount_amount"><span class="text-gray-600">Remise fixe:</span> <strong>-{{ form.discount_amount }}€</strong></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Signature Checkboxes -->
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Signatures</h3>
                                        <div class="space-y-3">
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.signed_by_customer"
                                                    type="checkbox"
                                                    class="w-4 h-4 text-amber-600 rounded focus:ring-2 focus:ring-amber-500"
                                                />
                                                <span class="ml-3 text-sm text-gray-700">
                                                    Signé par le client
                                                </span>
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="form.signed_by_staff"
                                                    type="checkbox"
                                                    class="w-4 h-4 text-amber-600 rounded focus:ring-2 focus:ring-amber-500"
                                                />
                                                <span class="ml-3 text-sm text-gray-700">
                                                    Signé par le personnel
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Warning Message -->
                                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex gap-3">
                                        <ExclamationTriangleIcon class="h-5 w-5 text-amber-600 flex-shrink-0" />
                                        <div class="text-sm text-amber-800">
                                            <p class="font-medium mb-1">Veuillez vérifier tous les détails</p>
                                            <p>Assurez-vous que tous les informations sont correctes avant de créer le contrat.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between mt-12">
                        <button
                            v-if="currentStep > 0"
                            @click="currentStep--"
                            type="button"
                            class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                        >
                            ← Précédent
                        </button>
                        <div v-else></div>

                        <div class="flex gap-3">
                            <Link
                                :href="route('tenant.contracts.index')"
                                class="px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-medium"
                            >
                                Annuler
                            </Link>
                            <button
                                v-if="currentStep < steps.length - 1"
                                @click="currentStep++"
                                type="button"
                                :disabled="!isStepValid(currentStep)"
                                class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Suivant →
                            </button>
                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ form.processing ? 'Création...' : 'Créer le contrat' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    BuildingOffice2Icon,
    ArchiveBoxIcon,
    UserIcon,
    DocumentTextIcon,
    CalendarIcon,
    CurrencyEuroIcon,
    ReceiptPercentIcon,
    KeyIcon,
    CheckIcon,
    PlusIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: Array,
    customers: Array,
    boxes: Array,
})

const currentStep = ref(0)

const steps = [
    { name: 'Box', description: 'Sélectionnez un box' },
    { name: 'Client', description: 'Sélectionnez un client' },
    { name: 'Création', description: 'Configurez le contrat' },
    { name: 'Validation', description: 'Contrôle' },
]

const form = useForm({
    site_id: '',
    customer_id: '',
    box_id: '',
    contract_number: '',
    status: 'draft',
    type: 'standard',
    start_date: '',
    end_date: '',
    notice_period_days: 30,
    auto_renew: true,
    renewal_period: 'monthly',
    monthly_price: '',
    deposit_amount: 0,
    deposit_paid: false,
    discount_percentage: 0,
    discount_amount: 0,
    billing_frequency: 'monthly',
    billing_day: 1,
    payment_method: 'card',
    auto_pay: false,
    access_code: '',
    key_given: false,
    key_returned: false,
    signed_by_customer: false,
    customer_signed_at: '',
    signed_by_staff: false,
    staff_user_id: '',
    termination_reason: '',
    termination_notes: '',
})

const filteredBoxes = computed(() => {
    if (!form.site_id) return props.boxes
    return props.boxes.filter((box) => box.site_id == form.site_id)
})

const selectedBox = computed(() => {
    if (!form.box_id) return null
    return props.boxes.find((box) => box.id == form.box_id)
})

const selectedCustomer = computed(() => {
    if (!form.customer_id) return null
    return props.customers.find((customer) => customer.id == form.customer_id)
})

const getCustomerName = (customer) => {
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getBoxLabel = (box) => {
    const parts = [box.number]
    if (box.site) parts.push(box.site.name)
    if (box.building) parts.push(box.building.name)
    if (box.floor) parts.push(`Étage ${box.floor.floor_number}`)
    return parts.join(' - ')
}

const updateMonthlyPrice = () => {
    if (selectedBox.value && !form.monthly_price) {
        form.monthly_price = selectedBox.value.base_price
    }
}

const isStepValid = (stepIndex) => {
    switch (stepIndex) {
        case 0: // Box step
            return form.site_id && form.box_id
        case 1: // Customer step
            return form.customer_id
        case 2: // Creation step
            return form.start_date && form.end_date && form.monthly_price
        case 3: // Validation step
            return true
        default:
            return false
    }
}

const submitForm = () => {
    form.post(route('tenant.contracts.store'))
}
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}

.slide-fade-enter-from {
    transform: translateX(10px);
    opacity: 0;
}

.slide-fade-leave-to {
    transform: translateX(-10px);
    opacity: 0;
}
</style>
