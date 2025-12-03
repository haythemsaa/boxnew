<template>
    <TenantLayout title="Nouveau Box">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-amber-50 to-orange-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.boxes.index')"
                        class="inline-flex items-center text-sm text-amber-600 hover:text-amber-800 transition-colors mb-4"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux boxes
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouveau Box</h1>
                            <p class="text-gray-500 mt-1">Ajoutez une nouvelle unité de stockage à votre inventaire</p>
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
                                        ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/25'
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
                                    currentStep >= step.number ? 'text-amber-600' : 'text-gray-400'
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
                        <!-- Step 1: Informations de base -->
                        <div v-show="currentStep === 1" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Informations de base</h3>
                                    <p class="text-sm text-gray-500">Identifiez votre nouveau box de stockage</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Nom et Code -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Nom du box <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all"
                                            :class="{ 'border-red-300': form.errors.name }"
                                            placeholder="ex: Box A-101"
                                        />
                                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Code du box <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.code"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all uppercase"
                                            :class="{ 'border-red-300': form.errors.code }"
                                            placeholder="ex: BOX-A101"
                                        />
                                        <p v-if="form.errors.code" class="mt-2 text-sm text-red-600">{{ form.errors.code }}</p>
                                    </div>
                                </div>

                                <!-- Site -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Site <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label
                                            v-for="site in sites"
                                            :key="site.id"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.site_id"
                                                :value="site.id"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-4 rounded-xl border-2 transition-all',
                                                'peer-checked:border-amber-500 peer-checked:bg-amber-50',
                                                'hover:border-amber-300 hover:bg-amber-50/50',
                                                form.site_id !== site.id ? 'border-gray-200' : ''
                                            ]">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <span class="font-medium text-gray-900 block">{{ site.name }}</span>
                                                        <span class="text-xs text-gray-500">{{ site.code }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <p v-if="form.errors.site_id" class="mt-2 text-sm text-red-600">{{ form.errors.site_id }}</p>
                                </div>

                                <!-- Bâtiment et Étage -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Bâtiment (optionnel)
                                        </label>
                                        <select
                                            v-model="form.building_id"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all"
                                        >
                                            <option value="">Aucun bâtiment</option>
                                            <option v-for="building in filteredBuildings" :key="building.id" :value="building.id">
                                                {{ building.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Étage (optionnel)
                                        </label>
                                        <select
                                            v-model="form.floor_id"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all"
                                        >
                                            <option value="">Aucun étage</option>
                                            <option v-for="floor in filteredFloors" :key="floor.id" :value="floor.id">
                                                {{ floor.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all resize-none"
                                        placeholder="Description du box (optionnel)"
                                    ></textarea>
                                </div>

                                <!-- Statut -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Statut <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-4 gap-3">
                                        <label
                                            v-for="status in statuses"
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
                                                'peer-checked:border-amber-500 peer-checked:bg-amber-50',
                                                'hover:border-amber-300 hover:bg-amber-50/50',
                                                form.status !== status.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="['w-8 h-8 rounded-full mx-auto mb-2 flex items-center justify-center', status.bgColor]">
                                                    <svg class="w-4 h-4" :class="status.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="status.icon" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900 text-xs">{{ status.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Dimensions & Prix -->
                        <div v-show="currentStep === 2" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Dimensions & Tarification</h3>
                                    <p class="text-sm text-gray-500">Définissez les dimensions et le prix du box</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Dimensions -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Dimensions (en mètres) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Longueur</label>
                                            <div class="relative">
                                                <input
                                                    v-model="form.length"
                                                    type="number"
                                                    step="0.1"
                                                    min="0.1"
                                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all pr-10"
                                                    :class="{ 'border-red-300': form.errors.length }"
                                                    placeholder="2.5"
                                                />
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">m</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Largeur</label>
                                            <div class="relative">
                                                <input
                                                    v-model="form.width"
                                                    type="number"
                                                    step="0.1"
                                                    min="0.1"
                                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all pr-10"
                                                    :class="{ 'border-red-300': form.errors.width }"
                                                    placeholder="2.0"
                                                />
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">m</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Hauteur</label>
                                            <div class="relative">
                                                <input
                                                    v-model="form.height"
                                                    type="number"
                                                    step="0.1"
                                                    min="0.1"
                                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all pr-10"
                                                    :class="{ 'border-red-300': form.errors.height }"
                                                    placeholder="2.5"
                                                />
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">m</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Volume calculé -->
                                <div v-if="calculatedVolume || calculatedArea" class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-xl p-4 border border-orange-100">
                                    <div class="flex items-center space-x-6">
                                        <div v-if="calculatedArea" class="flex items-center space-x-2">
                                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500">Surface</p>
                                                <p class="font-bold text-gray-900">{{ calculatedArea }} m²</p>
                                            </div>
                                        </div>
                                        <div v-if="calculatedVolume" class="flex items-center space-x-2">
                                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500">Volume</p>
                                                <p class="font-bold text-gray-900">{{ calculatedVolume }} m³</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Prix -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Prix de base (€/mois) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium">€</span>
                                        </div>
                                        <input
                                            v-model="form.base_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="w-full pl-10 pr-20 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all"
                                            :class="{ 'border-red-300': form.errors.base_price }"
                                            placeholder="150.00"
                                        />
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">/mois</span>
                                    </div>
                                    <p v-if="form.errors.base_price" class="mt-2 text-sm text-red-600">{{ form.errors.base_price }}</p>
                                </div>

                                <!-- Code d'accès -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Code d'accès
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.access_code"
                                            type="text"
                                            class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all"
                                            placeholder="ex: 1234"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Équipements -->
                        <div v-show="currentStep === 3" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-teal-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Équipements & Services</h3>
                                    <p class="text-sm text-gray-500">Sélectionnez les fonctionnalités du box</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <label
                                        v-for="feature in features"
                                        :key="feature.key"
                                        class="relative cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="form[feature.key]"
                                            class="peer sr-only"
                                        />
                                        <div :class="[
                                            'p-4 rounded-xl border-2 transition-all flex items-center space-x-3',
                                            'peer-checked:border-teal-500 peer-checked:bg-teal-50',
                                            'hover:border-teal-300 hover:bg-teal-50/50',
                                            !form[feature.key] ? 'border-gray-200' : ''
                                        ]">
                                            <div :class="[
                                                'w-10 h-10 rounded-lg flex items-center justify-center transition-colors',
                                                form[feature.key] ? 'bg-teal-500 text-white' : 'bg-gray-100 text-gray-400'
                                            ]">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="feature.icon" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-medium text-gray-900 block">{{ feature.label }}</span>
                                                <span class="text-xs text-gray-500">{{ feature.description }}</span>
                                            </div>
                                            <svg v-if="form[feature.key]" class="w-5 h-5 text-teal-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </label>
                                </div>

                                <!-- Notes -->
                                <div class="mt-6">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Notes additionnelles
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 transition-all resize-none"
                                        placeholder="Instructions spéciales ou remarques..."
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
                                    <p class="text-sm text-gray-500">Vérifiez les informations avant de créer le box</p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Info Box -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Box</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p><span class="text-gray-500">Nom:</span> <span class="font-medium">{{ form.name || '-' }}</span></p>
                                            <p><span class="text-gray-500">Code:</span> <span class="font-mono font-medium">{{ form.code?.toUpperCase() || '-' }}</span></p>
                                            <p>
                                                <span class="text-gray-500">Statut:</span>
                                                <span :class="statusColor" class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium">
                                                    {{ statusLabel }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Dimensions -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Dimensions</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p><span class="text-gray-500">L × l × h:</span> {{ form.length || 0 }} × {{ form.width || 0 }} × {{ form.height || 0 }} m</p>
                                            <p><span class="text-gray-500">Surface:</span> <span class="font-medium">{{ calculatedArea || 0 }} m²</span></p>
                                            <p><span class="text-gray-500">Volume:</span> <span class="font-medium">{{ calculatedVolume || 0 }} m³</span></p>
                                        </div>
                                    </div>

                                    <!-- Site & Prix -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Site & Prix</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p><span class="text-gray-500">Site:</span> <span class="font-medium">{{ selectedSiteName }}</span></p>
                                            <p><span class="text-gray-500">Prix:</span> <span class="font-bold text-emerald-600">{{ form.base_price || 0 }} €/mois</span></p>
                                        </div>
                                    </div>

                                    <!-- Équipements -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Équipements</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="feature in activeFeatures"
                                                :key="feature.key"
                                                class="px-2 py-1 bg-teal-100 text-teal-700 rounded-full text-xs font-medium"
                                            >
                                                {{ feature.label }}
                                            </span>
                                            <span v-if="activeFeatures.length === 0" class="text-gray-400 text-sm">Aucun</span>
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
                                    :href="route('tenant.boxes.index')"
                                    class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-medium transition-colors"
                                >
                                    Annuler
                                </Link>
                                <button
                                    v-if="currentStep < 4"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl font-medium shadow-lg shadow-amber-500/25 hover:shadow-xl hover:shadow-amber-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    {{ form.processing ? 'Création...' : 'Créer le box' }}
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
    sites: Array,
    buildings: Array,
    floors: Array,
})

const currentStep = ref(1)

const steps = [
    { number: 1, title: 'Informations' },
    { number: 2, title: 'Dimensions' },
    { number: 3, title: 'Équipements' },
    { number: 4, title: 'Récapitulatif' }
]

const statuses = [
    { value: 'available', label: 'Disponible', icon: 'M5 13l4 4L19 7', bgColor: 'bg-emerald-100', iconColor: 'text-emerald-600' },
    { value: 'occupied', label: 'Occupé', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
    { value: 'maintenance', label: 'Maintenance', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', bgColor: 'bg-amber-100', iconColor: 'text-amber-600' },
    { value: 'reserved', label: 'Réservé', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', bgColor: 'bg-purple-100', iconColor: 'text-purple-600' }
]

const features = [
    { key: 'climate_controlled', label: 'Climatisé', description: 'Température contrôlée', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    { key: 'has_electricity', label: 'Électricité', description: 'Prise électrique disponible', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { key: 'has_alarm', label: 'Alarme', description: 'Système de sécurité', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
    { key: 'has_24_7_access', label: 'Accès 24/7', description: 'Accessible à tout moment', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'has_wifi', label: 'WiFi', description: 'Connexion internet', icon: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.143 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0' },
    { key: 'has_shelving', label: 'Rayonnages', description: 'Étagères incluses', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { key: 'is_ground_floor', label: 'Rez-de-chaussée', description: 'Accès de plain-pied', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' }
]

const form = useForm({
    site_id: '',
    building_id: '',
    floor_id: '',
    name: '',
    code: '',
    description: '',
    length: null,
    width: null,
    height: null,
    base_price: null,
    status: 'available',
    climate_controlled: false,
    has_electricity: false,
    has_alarm: false,
    has_24_7_access: false,
    has_wifi: false,
    has_shelving: false,
    is_ground_floor: false,
    access_code: '',
    notes: '',
})

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        return form.name && form.code && form.site_id && form.status
    }
    if (currentStep.value === 2) {
        return form.length && form.width && form.height && form.base_price
    }
    return true
})

const calculatedVolume = computed(() => {
    if (form.length && form.width && form.height) {
        return (form.length * form.width * form.height).toFixed(2)
    }
    return null
})

const calculatedArea = computed(() => {
    if (form.length && form.width) {
        return (form.length * form.width).toFixed(2)
    }
    return null
})

const filteredBuildings = computed(() => {
    if (!form.site_id || !props.buildings) return []
    return props.buildings.filter(b => b.site_id == form.site_id)
})

const filteredFloors = computed(() => {
    if (!form.building_id || !props.floors) return []
    return props.floors.filter(f => f.building_id == form.building_id)
})

const selectedSiteName = computed(() => {
    if (!form.site_id || !props.sites) return '-'
    const site = props.sites.find(s => s.id == form.site_id)
    return site ? site.name : '-'
})

const statusLabel = computed(() => {
    const status = statuses.find(s => s.value === form.status)
    return status ? status.label : form.status
})

const statusColor = computed(() => {
    switch (form.status) {
        case 'available': return 'bg-emerald-100 text-emerald-700'
        case 'occupied': return 'bg-blue-100 text-blue-700'
        case 'maintenance': return 'bg-amber-100 text-amber-700'
        case 'reserved': return 'bg-purple-100 text-purple-700'
        default: return 'bg-gray-100 text-gray-700'
    }
})

const activeFeatures = computed(() => {
    return features.filter(f => form[f.key])
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
    form.post(route('tenant.boxes.store'))
}
</script>
