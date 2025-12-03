<template>
    <TenantLayout title="Nouveau Prospect">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-rose-50 to-pink-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.prospects.index')"
                        class="inline-flex items-center text-sm text-rose-600 hover:text-rose-800 transition-colors mb-4"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux prospects
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg shadow-rose-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouveau Prospect</h1>
                            <p class="text-gray-500 mt-1">Ajoutez un nouveau lead à votre pipeline commercial</p>
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
                                        ? 'bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-lg shadow-rose-500/25'
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
                                    currentStep >= step.number ? 'text-rose-600' : 'text-gray-400'
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
                        <!-- Step 1: Type & Identité -->
                        <div v-show="currentStep === 1" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Type & Identité</h3>
                                    <p class="text-sm text-gray-500">Identifiez votre nouveau prospect</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Type de prospect -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Type de prospect <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <label
                                            v-for="type in prospectTypes"
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
                                                'p-4 rounded-xl border-2 transition-all',
                                                'peer-checked:border-rose-500 peer-checked:bg-rose-50',
                                                'hover:border-rose-300 hover:bg-rose-50/50',
                                                form.type !== type.value ? 'border-gray-200' : ''
                                            ]">
                                                <div class="flex items-center space-x-3">
                                                    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', type.bgColor]">
                                                        <svg class="w-6 h-6" :class="type.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <span class="font-semibold text-gray-900 block">{{ type.label }}</span>
                                                        <span class="text-xs text-gray-500">{{ type.description }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Entreprise (si company) -->
                                <div v-if="form.type === 'company'" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Nom de l'entreprise <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <input
                                                v-model="form.company_name"
                                                type="text"
                                                class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all"
                                                :class="{ 'border-red-300': form.errors.company_name }"
                                                placeholder="Nom de l'entreprise"
                                            />
                                        </div>
                                        <p v-if="form.errors.company_name" class="mt-2 text-sm text-red-600">{{ form.errors.company_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">SIRET</label>
                                        <input
                                            v-model="form.siret"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all"
                                            placeholder="123 456 789 00012"
                                        />
                                    </div>
                                </div>

                                <!-- Prénom & Nom -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Prénom <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.first_name"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all"
                                            :class="{ 'border-red-300': form.errors.first_name }"
                                            placeholder="Jean"
                                        />
                                        <p v-if="form.errors.first_name" class="mt-2 text-sm text-red-600">{{ form.errors.first_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Nom <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.last_name"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all"
                                            :class="{ 'border-red-300': form.errors.last_name }"
                                            placeholder="Dupont"
                                        />
                                        <p v-if="form.errors.last_name" class="mt-2 text-sm text-red-600">{{ form.errors.last_name }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Contact -->
                        <div v-show="currentStep === 2" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-pink-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Coordonnées</h3>
                                    <p class="text-sm text-gray-500">Comment contacter ce prospect ?</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Email & Téléphone -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <input
                                                v-model="form.email"
                                                type="email"
                                                class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 transition-all"
                                                :class="{ 'border-red-300': form.errors.email }"
                                                placeholder="jean.dupont@email.com"
                                            />
                                        </div>
                                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Téléphone
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                            </div>
                                            <input
                                                v-model="form.phone"
                                                type="tel"
                                                class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 transition-all"
                                                placeholder="+33 6 12 34 56 78"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Adresse -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse</label>
                                    <input
                                        v-model="form.address"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 transition-all"
                                        placeholder="123 rue de la Paix"
                                    />
                                </div>

                                <!-- Code postal & Ville -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Code postal</label>
                                        <input
                                            v-model="form.postal_code"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 transition-all"
                                            placeholder="75001"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ville</label>
                                        <input
                                            v-model="form.city"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 transition-all"
                                            placeholder="Paris"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Projet -->
                        <div v-show="currentStep === 3" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Détails du Projet</h3>
                                    <p class="text-sm text-gray-500">Qualifiez le besoin de ce prospect</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Source -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Source <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-4 gap-3">
                                        <label
                                            v-for="source in sources"
                                            :key="source.value"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.source"
                                                :value="source.value"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-3 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-purple-500 peer-checked:bg-purple-50',
                                                'hover:border-purple-300 hover:bg-purple-50/50',
                                                form.source !== source.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="['w-8 h-8 rounded-lg mx-auto mb-1 flex items-center justify-center', source.bgColor]">
                                                    <svg class="w-4 h-4" :class="source.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="source.icon" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900 text-xs">{{ source.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Taille de box -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Taille de box souhaitée
                                    </label>
                                    <div class="grid grid-cols-5 gap-3">
                                        <label
                                            v-for="size in boxSizes"
                                            :key="size.value"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.box_size_interested"
                                                :value="size.value"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-3 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-purple-500 peer-checked:bg-purple-50',
                                                'hover:border-purple-300 hover:bg-purple-50/50',
                                                form.box_size_interested !== size.value ? 'border-gray-200' : ''
                                            ]">
                                                <span class="font-bold text-gray-900 block">{{ size.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Date & Budget -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Date d'entrée souhaitée
                                        </label>
                                        <input
                                            v-model="form.move_in_date"
                                            type="date"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Budget mensuel
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-gray-500 font-medium">€</span>
                                            </div>
                                            <input
                                                v-model.number="form.budget"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                class="w-full pl-10 pr-16 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all"
                                                placeholder="100"
                                            />
                                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">/mois</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all resize-none"
                                        placeholder="Informations complémentaires sur le prospect..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Récapitulatif -->
                        <div v-show="currentStep === 4" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Récapitulatif</h3>
                                    <p class="text-sm text-gray-500">Vérifiez les informations avant de créer le prospect</p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-xl p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Identité -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-rose-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Identité</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p class="font-semibold text-gray-900">{{ form.first_name }} {{ form.last_name }}</p>
                                            <p v-if="form.type === 'company'" class="text-gray-600">{{ form.company_name }}</p>
                                            <p>
                                                <span :class="typeColor" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                                    {{ form.type === 'company' ? 'Entreprise' : 'Particulier' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Contact -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Contact</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p><span class="text-gray-500">Email:</span> {{ form.email || '-' }}</p>
                                            <p><span class="text-gray-500">Tél:</span> {{ form.phone || '-' }}</p>
                                            <p v-if="form.city"><span class="text-gray-500">Ville:</span> {{ form.city }}</p>
                                        </div>
                                    </div>

                                    <!-- Projet -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Projet</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p><span class="text-gray-500">Source:</span> {{ selectedSourceLabel }}</p>
                                            <p><span class="text-gray-500">Taille:</span> {{ form.box_size_interested || 'Non défini' }}</p>
                                            <p v-if="form.move_in_date"><span class="text-gray-500">Entrée:</span> {{ form.move_in_date }}</p>
                                        </div>
                                    </div>

                                    <!-- Budget -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Budget</span>
                                        </div>
                                        <p v-if="form.budget" class="text-2xl font-bold text-emerald-600">{{ form.budget }} €/mois</p>
                                        <p v-else class="text-gray-400">Non défini</p>
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
                                    :href="route('tenant.prospects.index')"
                                    class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-medium transition-colors"
                                >
                                    Annuler
                                </Link>
                                <button
                                    v-if="currentStep < 4"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-xl font-medium shadow-lg shadow-rose-500/25 hover:shadow-xl hover:shadow-rose-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    {{ form.processing ? 'Enregistrement...' : 'Créer le prospect' }}
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

const currentStep = ref(1)

const steps = [
    { number: 1, title: 'Identité' },
    { number: 2, title: 'Contact' },
    { number: 3, title: 'Projet' },
    { number: 4, title: 'Récapitulatif' }
]

const prospectTypes = [
    { value: 'individual', label: 'Particulier', description: 'Client individuel', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
    { value: 'company', label: 'Entreprise', description: 'Client professionnel', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', bgColor: 'bg-purple-100', iconColor: 'text-purple-600' }
]

const sources = [
    { value: 'website', label: 'Site web', icon: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
    { value: 'phone', label: 'Téléphone', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', bgColor: 'bg-green-100', iconColor: 'text-green-600' },
    { value: 'email', label: 'Email', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', bgColor: 'bg-amber-100', iconColor: 'text-amber-600' },
    { value: 'referral', label: 'Parrainage', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', bgColor: 'bg-purple-100', iconColor: 'text-purple-600' },
    { value: 'walk_in', label: 'Visite', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', bgColor: 'bg-rose-100', iconColor: 'text-rose-600' },
    { value: 'social_media', label: 'Réseaux', icon: 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z', bgColor: 'bg-pink-100', iconColor: 'text-pink-600' },
    { value: 'other', label: 'Autre', icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', bgColor: 'bg-gray-100', iconColor: 'text-gray-600' }
]

const boxSizes = [
    { value: '1-5m²', label: '1-5 m²' },
    { value: '5-10m²', label: '5-10 m²' },
    { value: '10-20m²', label: '10-20 m²' },
    { value: '20-50m²', label: '20-50 m²' },
    { value: '+50m²', label: '+50 m²' }
]

const form = useForm({
    type: 'individual',
    first_name: '',
    last_name: '',
    company_name: '',
    siret: '',
    email: '',
    phone: '',
    address: '',
    postal_code: '',
    city: '',
    source: 'website',
    box_size_interested: '',
    move_in_date: '',
    budget: '',
    notes: '',
})

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        if (form.type === 'company') {
            return form.first_name && form.last_name && form.company_name
        }
        return form.first_name && form.last_name
    }
    if (currentStep.value === 2) {
        return form.email
    }
    if (currentStep.value === 3) {
        return form.source
    }
    return true
})

const typeColor = computed(() => {
    return form.type === 'company' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'
})

const selectedSourceLabel = computed(() => {
    const source = sources.find(s => s.value === form.source)
    return source ? source.label : form.source
})

const nextStep = () => {
    if (currentStep.value < 4 && canProceed.value) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const submit = () => {
    form.post(route('tenant.prospects.store'))
}
</script>
