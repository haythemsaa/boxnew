<template>
    <AuthenticatedLayout title="Nouveau Client">
        <div class="max-w-4xl mx-auto">
            <!-- En-tête avec fil d'Ariane -->
            <div class="mb-8">
                <Link
                    :href="route('tenant.customers.index')"
                    class="inline-flex items-center text-sm text-gray-500 hover:text-primary-600 transition-colors mb-4"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux clients
                </Link>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl shadow-lg shadow-primary-500/25">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Nouveau Client</h1>
                        <p class="text-sm text-gray-500">Remplissez les informations pour créer un nouveau client</p>
                    </div>
                </div>
            </div>

            <!-- Indicateur d'étapes -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <button
                        v-for="(step, index) in steps"
                        :key="index"
                        @click="currentStep = index"
                        class="flex items-center gap-3 group"
                        :class="{ 'cursor-default': index > maxVisitedStep }"
                        :disabled="index > maxVisitedStep"
                    >
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-full font-semibold text-sm transition-all duration-200"
                            :class="{
                                'bg-primary-600 text-white shadow-lg shadow-primary-500/30': currentStep === index,
                                'bg-emerald-500 text-white': index < currentStep,
                                'bg-gray-200 text-gray-500': index > currentStep && index <= maxVisitedStep,
                                'bg-gray-100 text-gray-400': index > maxVisitedStep
                            }"
                        >
                            <svg v-if="index < currentStep" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <div class="hidden sm:block">
                            <p
                                class="text-sm font-medium transition-colors"
                                :class="{
                                    'text-primary-600': currentStep === index,
                                    'text-emerald-600': index < currentStep,
                                    'text-gray-500': index > currentStep
                                }"
                            >
                                {{ step.title }}
                            </p>
                        </div>
                    </button>
                </div>
                <!-- Barre de progression -->
                <div class="mt-4 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-primary-500 to-primary-600 transition-all duration-500 ease-out"
                        :style="{ width: `${((currentStep + 1) / steps.length) * 100}%` }"
                    ></div>
                </div>
            </div>

            <!-- Formulaire -->
            <form @submit.prevent="submit">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Étape 1: Type de client -->
                    <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-x-4"
                        enter-to-class="opacity-100 translate-x-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 translate-x-0"
                        leave-to-class="opacity-0 -translate-x-4"
                        mode="out-in"
                    >
                        <div v-if="currentStep === 0" key="step-0" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Type de client</h2>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <label
                                    class="relative flex flex-col p-6 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"
                                    :class="form.type === 'individual' ? 'border-primary-500 bg-primary-50/50 shadow-md' : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input
                                        v-model="form.type"
                                        type="radio"
                                        value="individual"
                                        class="sr-only"
                                    />
                                    <div class="flex items-center gap-4 mb-3">
                                        <div
                                            class="p-3 rounded-xl transition-colors"
                                            :class="form.type === 'individual' ? 'bg-primary-100' : 'bg-gray-100'"
                                        >
                                            <svg class="h-6 w-6" :class="form.type === 'individual' ? 'text-primary-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">Particulier</p>
                                            <p class="text-sm text-gray-500">Personne physique</p>
                                        </div>
                                    </div>
                                    <div
                                        v-if="form.type === 'individual'"
                                        class="absolute top-4 right-4 p-1 bg-primary-500 rounded-full"
                                    >
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </label>

                                <label
                                    class="relative flex flex-col p-6 border-2 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"
                                    :class="form.type === 'company' ? 'border-primary-500 bg-primary-50/50 shadow-md' : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input
                                        v-model="form.type"
                                        type="radio"
                                        value="company"
                                        class="sr-only"
                                    />
                                    <div class="flex items-center gap-4 mb-3">
                                        <div
                                            class="p-3 rounded-xl transition-colors"
                                            :class="form.type === 'company' ? 'bg-primary-100' : 'bg-gray-100'"
                                        >
                                            <svg class="h-6 w-6" :class="form.type === 'company' ? 'text-primary-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">Entreprise</p>
                                            <p class="text-sm text-gray-500">Personne morale</p>
                                        </div>
                                    </div>
                                    <div
                                        v-if="form.type === 'company'"
                                        class="absolute top-4 right-4 p-1 bg-primary-500 rounded-full"
                                    >
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Étape 2: Informations personnelles/entreprise -->
                        <div v-else-if="currentStep === 1" key="step-1" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                                {{ form.type === 'individual' ? 'Informations personnelles' : 'Informations entreprise' }}
                            </h2>

                            <!-- Champs Particulier -->
                            <div v-if="form.type === 'individual'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Civilité</label>
                                        <div class="relative">
                                            <select
                                                v-model="form.civility"
                                                class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 appearance-none"
                                            >
                                                <option value="">Sélectionner</option>
                                                <option value="mr">Monsieur</option>
                                                <option value="mrs">Madame</option>
                                                <option value="ms">Mademoiselle</option>
                                                <option value="dr">Docteur</option>
                                                <option value="prof">Professeur</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Prénom <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.first_name"
                                            type="text"
                                            required
                                            placeholder="Jean"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            :class="{ 'ring-2 ring-red-500/50 bg-red-50': form.errors.first_name }"
                                        />
                                        <p v-if="form.errors.first_name || stepErrors.first_name" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ form.errors.first_name || stepErrors.first_name }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nom <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.last_name"
                                            type="text"
                                            required
                                            placeholder="Dupont"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            :class="{ 'ring-2 ring-red-500/50 bg-red-50': form.errors.last_name }"
                                        />
                                        <p v-if="form.errors.last_name || stepErrors.last_name" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ form.errors.last_name || stepErrors.last_name }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance</label>
                                        <input
                                            v-model="form.birth_date"
                                            type="date"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Lieu de naissance</label>
                                        <input
                                            v-model="form.birth_place"
                                            type="text"
                                            placeholder="Paris"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Champs Entreprise -->
                            <div v-else class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Raison sociale <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.company_name"
                                        type="text"
                                        required
                                        placeholder="Mon Entreprise SARL"
                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                        :class="{ 'ring-2 ring-red-500/50 bg-red-50': form.errors.company_name }"
                                    />
                                    <p v-if="form.errors.company_name || stepErrors.company_name" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ form.errors.company_name || stepErrors.company_name }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Numéro SIRET</label>
                                        <input
                                            v-model="form.siret"
                                            type="text"
                                            placeholder="123 456 789 00010"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                        />
                                        <p class="mt-1.5 text-xs text-gray-500">14 chiffres sans espaces</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">N° TVA Intracommunautaire</label>
                                        <input
                                            v-model="form.vat_number"
                                            type="text"
                                            placeholder="FR12345678901"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                        />
                                    </div>
                                </div>

                                <!-- Contact principal de l'entreprise -->
                                <div class="pt-4 border-t border-gray-100">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Contact principal
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Prénom du contact</label>
                                            <input
                                                v-model="form.first_name"
                                                type="text"
                                                placeholder="Jean"
                                                class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom du contact</label>
                                            <input
                                                v-model="form.last_name"
                                                type="text"
                                                placeholder="Dupont"
                                                class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 3: Contact -->
                        <div v-else-if="currentStep === 2" key="step-2" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Coordonnées</h2>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            required
                                            placeholder="client@exemple.fr"
                                            class="w-full pl-11 pr-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            :class="{ 'ring-2 ring-red-500/50 bg-red-50': form.errors.email || stepErrors.email }"
                                        />
                                    </div>
                                    <p v-if="form.errors.email || stepErrors.email" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ form.errors.email || stepErrors.email }}
                                    </p>
                                </div>

                                <!-- Téléphones dynamiques -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphones</label>
                                    <div class="space-y-3">
                                        <div class="flex gap-3">
                                            <div class="relative flex-1">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                </div>
                                                <input
                                                    v-model="form.phone"
                                                    type="tel"
                                                    placeholder="01 23 45 67 89"
                                                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                    :class="{ 'ring-2 ring-red-500/50 bg-red-50': stepErrors.phone }"
                                                />
                                            </div>
                                            <span class="inline-flex items-center px-3 py-2 bg-gray-100 rounded-xl text-xs font-medium text-gray-500">
                                                Fixe
                                            </span>
                                        </div>
                                        <p v-if="stepErrors.phone" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ stepErrors.phone }}
                                        </p>
                                        <div class="flex gap-3">
                                            <div class="relative flex-1">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <input
                                                    v-model="form.mobile"
                                                    type="tel"
                                                    placeholder="06 12 34 56 78"
                                                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                />
                                            </div>
                                            <span class="inline-flex items-center px-3 py-2 bg-primary-100 rounded-xl text-xs font-medium text-primary-600">
                                                Mobile
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pièce d'identité (accordéon) -->
                                <div class="border border-gray-200 rounded-xl overflow-hidden">
                                    <button
                                        type="button"
                                        @click="showIdSection = !showIdSection"
                                        class="w-full px-5 py-4 flex items-center justify-between text-left bg-gray-50 hover:bg-gray-100 transition-colors"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-700">Pièce d'identité (optionnel)</span>
                                        </div>
                                        <svg
                                            class="h-5 w-5 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-180': showIdSection }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <transition
                                        enter-active-class="transition-all duration-200 ease-out"
                                        enter-from-class="opacity-0 max-h-0"
                                        enter-to-class="opacity-100 max-h-96"
                                        leave-active-class="transition-all duration-150 ease-in"
                                        leave-from-class="opacity-100 max-h-96"
                                        leave-to-class="opacity-0 max-h-0"
                                    >
                                        <div v-if="showIdSection" class="p-5 space-y-4 border-t border-gray-200 overflow-hidden">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de document</label>
                                                    <select
                                                        v-model="form.id_type"
                                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 appearance-none"
                                                    >
                                                        <option value="">Sélectionner</option>
                                                        <option value="passport">Passeport</option>
                                                        <option value="id_card">Carte d'identité</option>
                                                        <option value="driver_license">Permis de conduire</option>
                                                        <option value="residence_permit">Titre de séjour</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Numéro du document</label>
                                                    <input
                                                        v-model="form.id_number"
                                                        type="text"
                                                        placeholder="123456789"
                                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                    />
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'émission</label>
                                                    <input
                                                        v-model="form.id_issue_date"
                                                        type="date"
                                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'expiration</label>
                                                    <input
                                                        v-model="form.id_expiry_date"
                                                        type="date"
                                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 4: Adresse -->
                        <div v-else-if="currentStep === 3" key="step-3" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Adresse</h2>

                            <div class="space-y-6">
                                <!-- Adresse principale -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Adresse <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.address"
                                            type="text"
                                            required
                                            placeholder="123 Rue de la Paix"
                                            class="w-full pl-11 pr-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            :class="{ 'ring-2 ring-red-500/50 bg-red-50': form.errors.address || stepErrors.address }"
                                        />
                                    </div>
                                    <p v-if="form.errors.address || stepErrors.address" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ form.errors.address || stepErrors.address }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Code postal <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.postal_code"
                                            type="text"
                                            required
                                            placeholder="75001"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            :class="{ 'ring-2 ring-red-500/50 bg-red-50': form.errors.postal_code || stepErrors.postal_code }"
                                        />
                                        <p v-if="form.errors.postal_code || stepErrors.postal_code" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ form.errors.postal_code || stepErrors.postal_code }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Ville <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.city"
                                            type="text"
                                            required
                                            placeholder="Paris"
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                            :class="{ 'ring-2 ring-red-500/50 bg-red-50': form.errors.city || stepErrors.city }"
                                        />
                                        <p v-if="form.errors.city || stepErrors.city" class="mt-1.5 text-sm text-red-600 field-error flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ form.errors.city || stepErrors.city }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Pays <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="form.country"
                                            required
                                            class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 appearance-none"
                                        >
                                            <option value="France">France</option>
                                            <option value="Belgique">Belgique</option>
                                            <option value="Suisse">Suisse</option>
                                            <option value="Luxembourg">Luxembourg</option>
                                            <option value="Canada">Canada</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Adresse de facturation -->
                                <div class="pt-6 border-t border-gray-200">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            Adresse de facturation
                                        </h3>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                v-model="sameAsPrimary"
                                                type="checkbox"
                                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                                @change="copyPrimaryAddress"
                                            />
                                            <span class="text-sm text-gray-600">Identique à l'adresse principale</span>
                                        </label>
                                    </div>

                                    <transition
                                        enter-active-class="transition-all duration-200 ease-out"
                                        enter-from-class="opacity-0 -translate-y-2"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition-all duration-150 ease-in"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-2"
                                    >
                                        <div v-if="!sameAsPrimary" class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                                                <input
                                                    v-model="form.billing_address"
                                                    type="text"
                                                    placeholder="Adresse de facturation"
                                                    class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                />
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Code postal</label>
                                                    <input
                                                        v-model="form.billing_postal_code"
                                                        type="text"
                                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                                                    <input
                                                        v-model="form.billing_city"
                                                        type="text"
                                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Pays</label>
                                                    <input
                                                        v-model="form.billing_country"
                                                        type="text"
                                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>

                        <!-- Étape 5: Finalisation -->
                        <div v-else-if="currentStep === 4" key="step-4" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Finalisation</h2>

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Score de crédit</label>
                                        <input
                                            v-model="form.credit_score"
                                            type="range"
                                            min="0"
                                            max="1000"
                                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                        />
                                        <div class="flex justify-between mt-2">
                                            <span class="text-xs text-gray-500">0</span>
                                            <span class="text-sm font-medium text-primary-600">{{ form.credit_score || 500 }}</span>
                                            <span class="text-xs text-gray-500">1000</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Statut <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex gap-3">
                                            <label
                                                v-for="status in statusOptions"
                                                :key="status.value"
                                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-xl cursor-pointer transition-all duration-200"
                                                :class="form.status === status.value ? `border-${status.color}-500 bg-${status.color}-50` : 'border-gray-200 hover:border-gray-300'"
                                            >
                                                <input
                                                    v-model="form.status"
                                                    type="radio"
                                                    :value="status.value"
                                                    class="sr-only"
                                                />
                                                <span
                                                    class="w-2 h-2 rounded-full"
                                                    :class="`bg-${status.color}-500`"
                                                ></span>
                                                <span class="text-sm font-medium text-gray-700">{{ status.label }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes internes</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="4"
                                        placeholder="Informations complémentaires sur ce client..."
                                        class="w-full px-4 py-3 bg-gray-50 border-0 rounded-xl text-sm placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-primary-500/20 transition-all duration-200 resize-none"
                                    ></textarea>
                                </div>

                                <!-- Récapitulatif -->
                                <div class="bg-gray-50 rounded-xl p-6">
                                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Récapitulatif</h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-500">Type</p>
                                            <p class="font-medium text-gray-900">{{ form.type === 'individual' ? 'Particulier' : 'Entreprise' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Nom</p>
                                            <p class="font-medium text-gray-900">
                                                {{ form.type === 'company' ? form.company_name : `${form.first_name} ${form.last_name}` }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Email</p>
                                            <p class="font-medium text-gray-900">{{ form.email || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Téléphone</p>
                                            <p class="font-medium text-gray-900">{{ form.mobile || form.phone || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Actions -->
                    <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <button
                            v-if="currentStep > 0"
                            type="button"
                            @click="previousStep"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Précédent
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-3">
                            <Link
                                :href="route('tenant.customers.index')"
                                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                            >
                                Annuler
                            </Link>

                            <button
                                v-if="currentStep < steps.length - 1"
                                type="button"
                                @click="nextStep"
                                class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30 transition-all duration-200"
                            >
                                Suivant
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>

                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-sm font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="!form.processing" class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Créer le client
                                </span>
                                <span v-else class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Création...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const currentStep = ref(0)
const maxVisitedStep = ref(0)
const showIdSection = ref(false)
const sameAsPrimary = ref(true)

const steps = [
    { title: 'Type', description: 'Particulier ou Entreprise' },
    { title: 'Identité', description: 'Informations personnelles' },
    { title: 'Contact', description: 'Email et téléphone' },
    { title: 'Adresse', description: 'Localisation' },
    { title: 'Finalisation', description: 'Dernières informations' },
]

const statusOptions = [
    { value: 'active', label: 'Actif', color: 'emerald' },
    { value: 'inactive', label: 'Inactif', color: 'gray' },
    { value: 'suspended', label: 'Suspendu', color: 'red' },
]

const form = useForm({
    type: 'individual',
    civility: '',
    first_name: '',
    last_name: '',
    company_name: '',
    siret: '',
    vat_number: '',
    email: '',
    phone: '',
    mobile: '',
    birth_date: '',
    birth_place: '',
    id_type: '',
    id_number: '',
    id_issue_date: '',
    id_expiry_date: '',
    address: '',
    city: '',
    postal_code: '',
    country: 'France',
    billing_address: '',
    billing_city: '',
    billing_postal_code: '',
    billing_country: '',
    credit_score: 500,
    notes: '',
    status: 'active',
})

// Validation errors for each step
const stepErrors = ref({})

const validateStep = (step) => {
    const errors = {}

    switch (step) {
        case 0: // Type
            // Pas de validation requise - type a une valeur par défaut
            break
        case 1: // Identité
            if (form.type === 'individual') {
                if (!form.first_name) errors.first_name = 'Le prénom est obligatoire'
                if (!form.last_name) errors.last_name = 'Le nom est obligatoire'
            } else {
                if (!form.company_name) errors.company_name = 'La raison sociale est obligatoire'
            }
            break
        case 2: // Contact
            if (!form.email) {
                errors.email = 'L\'email est obligatoire'
            } else {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                if (!emailRegex.test(form.email)) {
                    errors.email = 'Format d\'email invalide'
                }
            }
            if (form.phone && !/^[\d\s\+\-\(\)\.]{8,20}$/.test(form.phone)) {
                errors.phone = 'Format de téléphone invalide'
            }
            break
        case 3: // Adresse
            if (!form.address) errors.address = 'L\'adresse est obligatoire'
            if (!form.city) errors.city = 'La ville est obligatoire'
            if (!form.postal_code) errors.postal_code = 'Le code postal est obligatoire'
            break
        case 4: // Finalisation
            // Pas de champs obligatoires
            break
    }

    return errors
}

const nextStep = () => {
    const errors = validateStep(currentStep.value)
    stepErrors.value = errors

    if (Object.keys(errors).length > 0) {
        setTimeout(() => {
            const firstErrorField = document.querySelector('.field-error')
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
            }
        }, 100)
        return
    }

    if (currentStep.value < steps.length - 1) {
        currentStep.value++
        stepErrors.value = {}
        if (currentStep.value > maxVisitedStep.value) {
            maxVisitedStep.value = currentStep.value
        }
    }
}

const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--
        stepErrors.value = {}
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
    if (sameAsPrimary.value) {
        form.billing_address = form.address
        form.billing_city = form.city
        form.billing_postal_code = form.postal_code
        form.billing_country = form.country
    }
    form.post(route('tenant.customers.store'))
}
</script>
