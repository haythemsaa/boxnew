<template>
    <TenantLayout title="Modifier Client">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <Link
                    :href="route('tenant.customers.show', customer.id)"
                    class="inline-flex items-center text-blue-100 hover:text-white mb-4 transition-colors duration-200"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour au client
                </Link>

                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                        <component :is="form.type === 'company' ? BuildingOfficeIcon : UserIcon" class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Modifier {{ customerDisplayName }}</h1>
                        <p class="text-blue-100 mt-1">Mettez à jour les informations du client</p>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mt-8">
                    <div class="flex items-center justify-between">
                        <template v-for="(step, index) in steps" :key="step.number">
                            <div class="flex items-center">
                                <div
                                    :class="[
                                        'flex items-center justify-center w-12 h-12 rounded-xl border-2 transition-all duration-200',
                                        currentStep >= step.number
                                            ? 'bg-white border-white text-blue-600 shadow-lg'
                                            : 'border-white/30 text-white/60 bg-white/10'
                                    ]"
                                >
                                    <CheckIcon v-if="currentStep > step.number" class="h-6 w-6" />
                                    <component v-else :is="step.icon" class="h-6 w-6" />
                                </div>
                                <span
                                    :class="[
                                        'ml-3 text-sm font-semibold hidden sm:block',
                                        currentStep >= step.number ? 'text-white' : 'text-white/60'
                                    ]"
                                >
                                    {{ step.title }}
                                </span>
                            </div>
                            <div
                                v-if="index < steps.length - 1"
                                :class="[
                                    'flex-1 h-1 mx-4 rounded-full transition-all duration-300',
                                    currentStep > step.number ? 'bg-white' : 'bg-white/20'
                                ]"
                            ></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <form @submit.prevent="submit">
                    <!-- Step 1: Type -->
                    <div v-show="currentStep === 1" class="p-8 space-y-8">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                <UserIcon class="h-5 w-5 text-blue-600" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">Type de client</h3>
                        </div>

                        <!-- Type Selection -->
                        <div class="grid grid-cols-2 gap-6">
                            <label class="relative cursor-pointer group">
                                <input type="radio" v-model="form.type" value="individual" class="peer sr-only" />
                                <div class="p-8 border-2 rounded-2xl transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 border-gray-200 group-hover:border-blue-300 group-hover:shadow-lg">
                                    <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <UserIcon class="h-8 w-8 text-blue-600" />
                                    </div>
                                    <p class="text-center font-bold text-gray-900 text-lg">Particulier</p>
                                    <p class="text-center text-sm text-gray-500 mt-2">Personne physique</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer group">
                                <input type="radio" v-model="form.type" value="company" class="peer sr-only" />
                                <div class="p-8 border-2 rounded-2xl transition-all duration-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 group-hover:border-indigo-300 group-hover:shadow-lg">
                                    <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <BuildingOfficeIcon class="h-8 w-8 text-indigo-600" />
                                    </div>
                                    <p class="text-center font-bold text-gray-900 text-lg">Entreprise</p>
                                    <p class="text-center text-sm text-gray-500 mt-2">Personne morale</p>
                                </div>
                            </label>
                        </div>

                        <!-- Individual Fields -->
                        <div v-if="form.type === 'individual'" class="space-y-6 pt-6 border-t border-gray-100">
                            <div class="grid grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Civilité</label>
                                    <select v-model="form.civility" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200">
                                        <option value="">--</option>
                                        <option v-for="opt in civilityOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénom <span class="text-red-500">*</span></label>
                                    <input v-model="form.first_name" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" :class="{ 'border-red-300 bg-red-50': form.errors.first_name }" />
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom <span class="text-red-500">*</span></label>
                                    <input v-model="form.last_name" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" :class="{ 'border-red-300 bg-red-50': form.errors.last_name }" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                                    <input v-model="form.birth_date" type="date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Lieu de naissance</label>
                                    <input v-model="form.birth_place" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                            </div>
                        </div>

                        <!-- Company Fields -->
                        <div v-if="form.type === 'company'" class="space-y-6 pt-6 border-t border-gray-100">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Raison sociale <span class="text-red-500">*</span></label>
                                <input v-model="form.company_name" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" :class="{ 'border-red-300 bg-red-50': form.errors.company_name }" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SIRET</label>
                                    <input v-model="form.siret" type="text" placeholder="123 456 789 00010" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">N° TVA intracommunautaire</label>
                                    <input v-model="form.vat_number" type="text" placeholder="FR12345678901" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénom du contact</label>
                                    <input v-model="form.first_name" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom du contact</label>
                                    <input v-model="form.last_name" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Contact -->
                    <div v-show="currentStep === 2" class="p-8 space-y-8">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                <EnvelopeIcon class="h-5 w-5 text-blue-600" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">Coordonnées</h3>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <EnvelopeIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input v-model="form.email" type="email" class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" :class="{ 'border-red-300 bg-red-50': form.errors.email }" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone fixe</label>
                                    <div class="relative">
                                        <PhoneIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input v-model="form.phone" type="tel" placeholder="01 23 45 67 89" class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone mobile</label>
                                    <div class="relative">
                                        <DevicePhoneMobileIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input v-model="form.mobile" type="tel" placeholder="06 12 34 56 78" class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ID Section -->
                        <div class="pt-6 border-t border-gray-100 space-y-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <IdentificationIcon class="h-5 w-5 text-gray-600" />
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Pièce d'identité</h4>
                                    <p class="text-sm text-gray-500">Optionnel - pour vérification</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de pièce</label>
                                    <select v-model="form.id_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200">
                                        <option value="">Sélectionner</option>
                                        <option v-for="opt in idTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Numéro</label>
                                    <input v-model="form.id_number" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de délivrance</label>
                                    <input v-model="form.id_issue_date" type="date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'expiration</label>
                                    <input v-model="form.id_expiry_date" type="date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Address -->
                    <div v-show="currentStep === 3" class="p-8 space-y-8">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                <MapPinIcon class="h-5 w-5 text-blue-600" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">Adresse principale</h3>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Adresse <span class="text-red-500">*</span></label>
                                <input v-model="form.address" type="text" placeholder="Numéro et nom de rue" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" :class="{ 'border-red-300 bg-red-50': form.errors.address }" />
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Code postal <span class="text-red-500">*</span></label>
                                    <input v-model="form.postal_code" type="text" placeholder="75001" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" :class="{ 'border-red-300 bg-red-50': form.errors.postal_code }" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Ville <span class="text-red-500">*</span></label>
                                    <input v-model="form.city" type="text" placeholder="Paris" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" :class="{ 'border-red-300 bg-red-50': form.errors.city }" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                                    <input v-model="form.country" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                                </div>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="pt-6 border-t border-gray-100 space-y-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                        <CreditCardIcon class="h-5 w-5 text-emerald-600" />
                                    </div>
                                    <h4 class="font-semibold text-gray-900">Adresse de facturation</h4>
                                </div>
                                <label class="flex items-center text-sm cursor-pointer bg-gray-50 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors duration-200">
                                    <input v-model="sameAsPrimary" type="checkbox" @change="copyPrimaryAddress" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    <span class="ml-2 text-gray-700 font-medium">Identique à l'adresse principale</span>
                                </label>
                            </div>

                            <div v-if="!sameAsPrimary" class="space-y-4 p-6 bg-gray-50 rounded-xl">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                                    <input v-model="form.billing_address" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200 bg-white" />
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                                        <input v-model="form.billing_postal_code" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200 bg-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                                        <input v-model="form.billing_city" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200 bg-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                                        <input v-model="form.billing_country" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200 bg-white" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Additional -->
                    <div v-show="currentStep === 4" class="p-8 space-y-8">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                <DocumentTextIcon class="h-5 w-5 text-blue-600" />
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">Informations complémentaires</h3>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Score de crédit (0-1000)</label>
                                <input v-model="form.credit_score" type="number" min="0" max="1000" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                                <div class="flex flex-wrap gap-2">
                                    <label v-for="opt in statusOptions" :key="opt.value" class="cursor-pointer">
                                        <input type="radio" v-model="form.status" :value="opt.value" class="peer sr-only" />
                                        <span :class="[
                                            'inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold border-2 transition-all duration-200',
                                            form.status === opt.value
                                                ? opt.activeColor
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                        ]">
                                            {{ opt.label }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes internes</label>
                            <textarea v-model="form.notes" rows="4" placeholder="Remarques sur ce client..." class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all duration-200 resize-none"></textarea>
                        </div>

                        <!-- Summary -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
                            <div class="flex items-center space-x-2 mb-4">
                                <CheckCircleIcon class="w-5 h-5 text-blue-600" />
                                <h4 class="font-semibold text-gray-900">Récapitulatif des modifications</h4>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="flex justify-between p-3 bg-white/60 rounded-lg">
                                    <span class="text-gray-500">Type</span>
                                    <span class="font-semibold text-gray-900">{{ form.type === 'company' ? 'Entreprise' : 'Particulier' }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-white/60 rounded-lg">
                                    <span class="text-gray-500">Nom</span>
                                    <span class="font-semibold text-gray-900">{{ customerDisplayName }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-white/60 rounded-lg">
                                    <span class="text-gray-500">Email</span>
                                    <span class="font-semibold text-gray-900">{{ form.email || '-' }}</span>
                                </div>
                                <div class="flex justify-between p-3 bg-white/60 rounded-lg">
                                    <span class="text-gray-500">Ville</span>
                                    <span class="font-semibold text-gray-900">{{ form.city || '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <div>
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-medium transition-colors duration-200 flex items-center space-x-2"
                            >
                                <ArrowLeftIcon class="w-4 h-4" />
                                <span>Précédent</span>
                            </button>
                        </div>
                        <div class="flex gap-3">
                            <Link
                                :href="route('tenant.customers.show', customer.id)"
                                class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-100 transition-colors duration-200"
                            >
                                Annuler
                            </Link>
                            <button
                                v-if="currentStep < totalSteps"
                                type="button"
                                @click="nextStep"
                                :disabled="!canProceed"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                            >
                                <span>Suivant</span>
                                <ArrowRightIcon class="w-4 h-4" />
                            </button>
                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-semibold hover:from-emerald-700 hover:to-teal-700 transition-all duration-200 shadow-lg shadow-emerald-200 disabled:opacity-50 flex items-center space-x-2"
                            >
                                <CheckIcon v-if="!form.processing" class="w-5 h-5" />
                                <ArrowPathIcon v-else class="w-5 h-5 animate-spin" />
                                <span>{{ form.processing ? 'Enregistrement...' : 'Mettre à jour' }}</span>
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
    ArrowLeftIcon,
    ArrowRightIcon,
    ArrowPathIcon,
    UserIcon,
    BuildingOfficeIcon,
    EnvelopeIcon,
    PhoneIcon,
    DevicePhoneMobileIcon,
    MapPinIcon,
    IdentificationIcon,
    CreditCardIcon,
    DocumentTextIcon,
    CheckIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customer: Object,
})

const currentStep = ref(1)
const totalSteps = 4
const sameAsPrimary = ref(false)

const form = useForm({
    type: props.customer.type,
    civility: props.customer.civility,
    first_name: props.customer.first_name,
    last_name: props.customer.last_name,
    company_name: props.customer.company_name,
    siret: props.customer.siret,
    vat_number: props.customer.vat_number,
    email: props.customer.email,
    phone: props.customer.phone,
    mobile: props.customer.mobile,
    birth_date: props.customer.birth_date,
    birth_place: props.customer.birth_place,
    id_type: props.customer.id_type,
    id_number: props.customer.id_number,
    id_issue_date: props.customer.id_issue_date,
    id_expiry_date: props.customer.id_expiry_date,
    address: props.customer.address,
    city: props.customer.city,
    postal_code: props.customer.postal_code,
    country: props.customer.country || 'France',
    billing_address: props.customer.billing_address,
    billing_city: props.customer.billing_city,
    billing_postal_code: props.customer.billing_postal_code,
    billing_country: props.customer.billing_country,
    credit_score: props.customer.credit_score,
    notes: props.customer.notes,
    status: props.customer.status,
})

const steps = [
    { number: 1, title: 'Type', icon: UserIcon },
    { number: 2, title: 'Contact', icon: EnvelopeIcon },
    { number: 3, title: 'Adresse', icon: MapPinIcon },
    { number: 4, title: 'Compléments', icon: DocumentTextIcon },
]

const civilityOptions = [
    { value: 'mr', label: 'M.' },
    { value: 'mrs', label: 'Mme' },
    { value: 'ms', label: 'Mlle' },
    { value: 'dr', label: 'Dr' },
    { value: 'prof', label: 'Pr' },
]

const idTypeOptions = [
    { value: 'id_card', label: "Carte d'identité" },
    { value: 'passport', label: 'Passeport' },
    { value: 'driver_license', label: 'Permis de conduire' },
    { value: 'residence_permit', label: 'Titre de séjour' },
]

const statusOptions = [
    { value: 'active', label: 'Actif', activeColor: 'border-emerald-500 bg-emerald-50 text-emerald-700' },
    { value: 'inactive', label: 'Inactif', activeColor: 'border-gray-500 bg-gray-50 text-gray-700' },
    { value: 'suspended', label: 'Suspendu', activeColor: 'border-red-500 bg-red-50 text-red-700' },
]

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            if (form.type === 'individual') {
                return form.first_name && form.last_name
            }
            return form.company_name
        case 2:
            return form.email
        case 3:
            return form.address && form.city && form.postal_code
        case 4:
            return true
        default:
            return false
    }
})

const nextStep = () => {
    if (currentStep.value < totalSteps && canProceed.value) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const copyPrimaryAddress = () => {
    if (sameAsPrimary.value) {
        form.billing_address = form.address
        form.billing_city = form.city
        form.billing_postal_code = form.postal_code
        form.billing_country = form.country
    } else {
        form.billing_address = ''
        form.billing_city = ''
        form.billing_postal_code = ''
        form.billing_country = ''
    }
}

const submit = () => {
    form.put(route('tenant.customers.update', props.customer.id))
}

const customerDisplayName = computed(() => {
    if (form.type === 'company' && form.company_name) {
        return form.company_name
    }
    return `${form.first_name || ''} ${form.last_name || ''}`.trim() || 'Client'
})
</script>
