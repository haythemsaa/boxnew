<template>
    <MobileLayout title="Réserver une Box">
        <!-- Enhanced Step Indicator with Labels -->
        <div class="mb-8">
            <!-- Progress Bar Background -->
            <div class="relative">
                <div class="absolute top-4 left-0 right-0 h-1 bg-gray-200 rounded-full mx-8"></div>
                <div
                    class="absolute top-4 left-0 h-1 bg-gradient-to-r from-primary-500 to-primary-600 rounded-full mx-8 transition-all duration-500 ease-out"
                    :style="{ width: `calc(${((step - 1) / (steps.length - 1)) * 100}% - 4rem)` }"
                ></div>

                <!-- Step Circles -->
                <div class="relative flex justify-between px-4">
                    <div
                        v-for="(stepInfo, index) in steps"
                        :key="index"
                        class="flex flex-col items-center"
                    >
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-semibold text-sm transition-all duration-300 shadow-sm"
                            :class="[
                                step > index + 1
                                    ? 'bg-gradient-to-br from-green-400 to-green-500 text-white scale-100'
                                    : step === index + 1
                                        ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white ring-4 ring-primary-100 scale-110'
                                        : 'bg-white text-gray-400 border-2 border-gray-200'
                            ]"
                        >
                            <CheckIcon v-if="step > index + 1" class="w-5 h-5" />
                            <span v-else>{{ index + 1 }}</span>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium transition-colors duration-300 text-center"
                            :class="step >= index + 1 ? 'text-primary-600' : 'text-gray-400'"
                        >
                            {{ stepInfo }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Select Site -->
        <Transition name="slide-fade" mode="out-in">
        <div v-if="step === 1" key="step1">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-primary-100 to-primary-200 rounded-2xl mb-3">
                    <MapPinIcon class="w-8 h-8 text-primary-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Choisir un site</h2>
                <p class="text-gray-500 mt-1">Selectionnez le site le plus proche de chez vous</p>
            </div>

            <div class="relative mb-5">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                </div>
                <input
                    v-model="siteSearch"
                    type="text"
                    placeholder="Rechercher par ville ou nom..."
                    class="w-full pl-12 pr-4 py-4 bg-white border-0 rounded-2xl shadow-sm focus:ring-2 focus:ring-primary-500 focus:shadow-md transition-shadow duration-200"
                />
            </div>

            <div class="space-y-3">
                <button
                    v-for="site in filteredSites"
                    :key="site.id"
                    @click="selectSite(site)"
                    class="w-full bg-white rounded-2xl shadow-sm p-5 text-left transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 active:scale-[0.98]"
                    :class="selectedSite?.id === site.id ? 'ring-2 ring-primary-500 shadow-primary-100/50 shadow-lg' : 'hover:shadow-md'"
                >
                    <div class="flex items-center">
                        <div
                            class="w-14 h-14 rounded-2xl flex items-center justify-center mr-4 transition-colors duration-200"
                            :class="selectedSite?.id === site.id ? 'bg-gradient-to-br from-primary-500 to-primary-600' : 'bg-gradient-to-br from-primary-50 to-primary-100'"
                        >
                            <BuildingOfficeIcon
                                class="w-7 h-7 transition-colors duration-200"
                                :class="selectedSite?.id === site.id ? 'text-white' : 'text-primary-600'"
                            />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 text-lg truncate">{{ site.name }}</h3>
                            <p class="text-sm text-gray-500 flex items-center mt-1">
                                <MapPinIcon class="w-4 h-4 mr-1 flex-shrink-0" />
                                <span class="truncate">{{ site.city }}</span>
                            </p>
                        </div>
                        <div class="text-right ml-3">
                            <div class="inline-flex items-center px-3 py-1.5 rounded-full bg-green-50 border border-green-100">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                <span class="text-sm font-semibold text-green-700">{{ site.boxes?.length || 0 }} dispo</span>
                            </div>
                        </div>
                    </div>
                </button>
            </div>

            <div v-if="filteredSites.length === 0" class="text-center py-16">
                <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <BuildingOfficeIcon class="w-10 h-10 text-gray-300" />
                </div>
                <p class="text-gray-500 font-medium">Aucun site trouve</p>
                <p class="text-gray-400 text-sm mt-1">Essayez une autre recherche</p>
            </div>
        </div>
        </Transition>

        <!-- Step 2: Select Box Size -->
        <Transition name="slide-fade" mode="out-in">
        <div v-if="step === 2" key="step2">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl mb-3">
                    <CubeIcon class="w-8 h-8 text-blue-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Choisir votre box</h2>
                <p class="text-gray-500 mt-1">
                    <span class="inline-flex items-center">
                        <MapPinIcon class="w-4 h-4 mr-1" />
                        {{ selectedSite?.name }}
                    </span>
                </p>
            </div>

            <!-- Size Filter Pills -->
            <div class="flex space-x-2 mb-5 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
                <button
                    v-for="size in sizeFilters"
                    :key="size.value"
                    @click="selectedSize = size.value"
                    :class="[
                        'px-5 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-200 shadow-sm',
                        selectedSize === size.value
                            ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-primary-200 shadow-lg scale-105'
                            : 'bg-white text-gray-600 hover:bg-gray-50 hover:shadow-md'
                    ]"
                >
                    {{ size.label }}
                    <span v-if="size.value !== 'all'" class="ml-1 opacity-70">{{ size.icon }}</span>
                </button>
            </div>

            <!-- Box Cards Grid -->
            <div class="grid grid-cols-1 gap-4">
                <button
                    v-for="box in filteredBoxes"
                    :key="box.id"
                    @click="selectBox(box)"
                    class="w-full bg-white rounded-2xl overflow-hidden text-left transition-all duration-200 hover:shadow-lg active:scale-[0.98]"
                    :class="selectedBox?.id === box.id ? 'ring-2 ring-primary-500 shadow-lg shadow-primary-100/50' : 'shadow-sm hover:shadow-md'"
                >
                    <!-- Box Visual Header -->
                    <div
                        class="h-24 relative overflow-hidden"
                        :class="selectedBox?.id === box.id ? 'bg-gradient-to-br from-primary-500 to-primary-600' : 'bg-gradient-to-br from-gray-100 to-gray-200'"
                    >
                        <!-- Decorative Box Pattern -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="grid grid-cols-3 gap-1 opacity-30">
                                <div v-for="i in 9" :key="i" class="w-6 h-6 rounded" :class="selectedBox?.id === box.id ? 'bg-white' : 'bg-gray-400'"></div>
                            </div>
                        </div>
                        <!-- Volume Badge -->
                        <div class="absolute top-3 right-3">
                            <div
                                class="px-3 py-1.5 rounded-full text-sm font-bold backdrop-blur-sm"
                                :class="selectedBox?.id === box.id ? 'bg-white/20 text-white' : 'bg-white/80 text-gray-700'"
                            >
                                {{ box.volume }} m³
                            </div>
                        </div>
                        <!-- Box Number -->
                        <div class="absolute bottom-3 left-4">
                            <span
                                class="text-2xl font-bold"
                                :class="selectedBox?.id === box.id ? 'text-white' : 'text-gray-700'"
                            >
                                Box {{ box.number }}
                            </span>
                        </div>
                    </div>

                    <!-- Box Details -->
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <!-- Size Indicator -->
                                <div class="flex items-end space-x-0.5">
                                    <div class="w-1 rounded-full" :class="[getBoxSizeClass(box.volume, 1), selectedBox?.id === box.id ? 'bg-primary-400' : 'bg-gray-300']"></div>
                                    <div class="w-1 rounded-full" :class="[getBoxSizeClass(box.volume, 2), selectedBox?.id === box.id && box.volume >= 5 ? 'bg-primary-400' : box.volume >= 5 ? 'bg-gray-300' : 'bg-gray-200']"></div>
                                    <div class="w-1 rounded-full" :class="[getBoxSizeClass(box.volume, 3), selectedBox?.id === box.id && box.volume >= 15 ? 'bg-primary-400' : box.volume >= 15 ? 'bg-gray-300' : 'bg-gray-200']"></div>
                                </div>
                                <span class="text-sm text-gray-500">
                                    {{ box.volume < 5 ? 'Petit' : box.volume < 15 ? 'Moyen' : 'Grand' }}
                                </span>
                            </div>
                            <div class="text-right">
                                <div class="flex items-baseline">
                                    <span class="text-2xl font-bold text-primary-600">{{ (box.monthly_price * 1.20).toFixed(0) }}</span>
                                    <span class="text-lg font-bold text-primary-600">,{{ ((box.monthly_price * 1.20).toFixed(2)).split('.')[1] }}€</span>
                                </div>
                                <p class="text-xs text-gray-400 font-medium">/mois TTC</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selection Indicator -->
                    <div
                        v-if="selectedBox?.id === box.id"
                        class="bg-primary-50 px-4 py-2 flex items-center justify-center border-t border-primary-100"
                    >
                        <CheckCircleIcon class="w-5 h-5 text-primary-600 mr-2" />
                        <span class="text-sm font-semibold text-primary-600">Selectionne</span>
                    </div>
                </button>
            </div>

            <div v-if="filteredBoxes.length === 0" class="text-center py-16">
                <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <CubeIcon class="w-10 h-10 text-gray-300" />
                </div>
                <p class="text-gray-500 font-medium">Aucun box disponible</p>
                <p class="text-gray-400 text-sm mt-1">Essayez une autre taille</p>
            </div>
        </div>
        </Transition>

        <!-- Step 3: Select Dates & Options -->
        <Transition name="slide-fade" mode="out-in">
        <div v-if="step === 3" key="step3">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-100 to-purple-200 rounded-2xl mb-3">
                    <CalendarDaysIcon class="w-8 h-8 text-purple-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Options et date</h2>
                <p class="text-gray-500 mt-1">Box {{ selectedBox?.number }} - {{ selectedBox?.volume }} m³</p>
            </div>

            <!-- Date Selection Card -->
            <div class="bg-white rounded-2xl shadow-sm p-5 mb-4 border border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center mr-3">
                        <CalendarDaysIcon class="w-5 h-5 text-primary-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">Date d'entree</h3>
                        <p class="text-sm text-gray-500">Quand souhaitez-vous commencer ?</p>
                    </div>
                </div>
                <input
                    type="date"
                    v-model="startDate"
                    :min="minStartDate"
                    class="w-full px-4 py-4 border-2 border-gray-100 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-gray-50 transition-all duration-200 text-lg font-medium"
                />
            </div>

            <!-- Options Cards -->
            <div class="space-y-3">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide px-1">Options supplementaires</h3>

                <!-- Depot de garantie -->
                <label
                    class="block bg-white rounded-2xl shadow-sm p-5 cursor-pointer transition-all duration-200 border-2"
                    :class="includeDeposit ? 'border-primary-500 shadow-primary-100/50 shadow-md' : 'border-transparent hover:shadow-md'"
                >
                    <div class="flex items-start">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 transition-colors duration-200"
                            :class="includeDeposit ? 'bg-primary-100' : 'bg-gray-100'"
                        >
                            <ShieldCheckIcon
                                class="w-6 h-6 transition-colors duration-200"
                                :class="includeDeposit ? 'text-primary-600' : 'text-gray-400'"
                            />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-semibold text-gray-900">Depot de garantie</h4>
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        v-model="includeDeposit"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500">2 mois de loyer HT - Rembourse a la fin</p>
                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-sm font-semibold">
                                {{ (selectedBox?.monthly_price * 2).toFixed(2) }}€
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Assurance -->
                <label
                    class="block bg-white rounded-2xl shadow-sm p-5 cursor-pointer transition-all duration-200 border-2"
                    :class="includeInsurance ? 'border-green-500 shadow-green-100/50 shadow-md' : 'border-transparent hover:shadow-md'"
                >
                    <div class="flex items-start">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 transition-colors duration-200"
                            :class="includeInsurance ? 'bg-green-100' : 'bg-gray-100'"
                        >
                            <ShieldExclamationIcon
                                class="w-6 h-6 transition-colors duration-200"
                                :class="includeInsurance ? 'text-green-600' : 'text-gray-400'"
                            />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center">
                                    <h4 class="font-semibold text-gray-900">Assurance</h4>
                                    <span class="ml-2 px-2 py-0.5 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Recommande</span>
                                </div>
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        v-model="includeInsurance"
                                        class="sr-only peer"
                                    />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500">Protection complete de vos biens</p>
                            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-green-50 text-green-700 text-sm font-semibold">
                                {{ insuranceTTC.toFixed(2) }}€/mois
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>
        </Transition>

        <!-- Step 4: Confirmation & Summary -->
        <Transition name="slide-fade" mode="out-in">
        <div v-if="step === 4" key="step4">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-100 to-amber-200 rounded-2xl mb-3">
                    <ClipboardDocumentCheckIcon class="w-8 h-8 text-amber-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Recapitulatif</h2>
                <p class="text-gray-500 mt-1">Verifiez votre reservation</p>
            </div>

            <!-- Reservation Details Card -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4 border border-gray-100">
                <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-5 py-4">
                    <div class="flex items-center text-white">
                        <CubeIcon class="w-6 h-6 mr-3 opacity-80" />
                        <div>
                            <h3 class="font-bold text-lg">Box {{ selectedBox?.number }}</h3>
                            <p class="text-primary-100 text-sm">{{ selectedBox?.volume }} m³ - {{ selectedSite?.name }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mr-3">
                            <MapPinIcon class="w-5 h-5 text-gray-500" />
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Site</p>
                            <p class="font-semibold text-gray-900">{{ selectedSite?.name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mr-3">
                            <CalendarDaysIcon class="w-5 h-5 text-gray-500" />
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Date de debut</p>
                            <p class="font-semibold text-gray-900">{{ formatDate(startDate) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Breakdown Card -->
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4 border border-gray-100">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 flex items-center">
                        <BanknotesIcon class="w-5 h-5 mr-2 text-gray-400" />
                        A payer maintenant
                    </h3>
                </div>
                <div class="p-5">
                    <div class="space-y-3">
                        <!-- Monthly Rent -->
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                                    <HomeIcon class="w-4 h-4 text-primary-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">1er mois de location</p>
                                    <p class="text-xs text-gray-400">TVA 20% incluse</p>
                                </div>
                            </div>
                            <span class="font-semibold text-gray-900">{{ monthlyPriceTTC.toFixed(2) }}€</span>
                        </div>

                        <!-- Deposit -->
                        <div v-if="includeDeposit" class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <ShieldCheckIcon class="w-4 h-4 text-blue-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Depot de garantie</p>
                                    <p class="text-xs text-gray-400">Hors TVA - Remboursable</p>
                                </div>
                            </div>
                            <span class="font-semibold text-gray-900">{{ (selectedBox?.monthly_price * 2).toFixed(2) }}€</span>
                        </div>

                        <!-- Insurance -->
                        <div v-if="includeInsurance" class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <ShieldExclamationIcon class="w-4 h-4 text-green-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Assurance</p>
                                    <p class="text-xs text-gray-400">Taxe 9% incluse</p>
                                </div>
                            </div>
                            <span class="font-semibold text-gray-900">{{ insuranceTTC.toFixed(2) }}€</span>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="mt-4 pt-4 border-t-2 border-dashed border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold text-gray-900">Total TTC</span>
                            <div class="text-right">
                                <span class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-primary-500 bg-clip-text text-transparent">{{ totalAmount.toFixed(2) }}€</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terms Checkbox -->
            <label class="flex items-start bg-white rounded-2xl shadow-sm p-5 mb-4 cursor-pointer border-2 transition-all duration-200 hover:shadow-md"
                :class="acceptTerms ? 'border-primary-500' : 'border-transparent'"
            >
                <div class="relative flex items-center">
                    <input
                        type="checkbox"
                        v-model="acceptTerms"
                        class="sr-only peer"
                    />
                    <div class="w-6 h-6 border-2 border-gray-300 rounded-lg peer-checked:bg-primary-600 peer-checked:border-primary-600 flex items-center justify-center transition-all duration-200">
                        <CheckIcon v-if="acceptTerms" class="w-4 h-4 text-white" />
                    </div>
                </div>
                <span class="ml-4 text-sm text-gray-600 leading-relaxed">
                    J'accepte les <a href="#" class="text-primary-600 font-semibold hover:underline">conditions generales</a> et la <a href="#" class="text-primary-600 font-semibold hover:underline">politique de confidentialite</a>
                </span>
            </label>
        </div>
        </Transition>

        <!-- Step 5: Payment -->
        <Transition name="slide-fade" mode="out-in">
        <div v-if="step === 5" key="step5">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-100 to-green-200 rounded-2xl mb-3">
                    <CreditCardIcon class="w-8 h-8 text-green-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Paiement securise</h2>
                <div class="mt-2 inline-flex items-center px-4 py-2 rounded-full bg-primary-50">
                    <span class="text-primary-600 font-bold text-lg">{{ totalAmount.toFixed(2) }}€</span>
                    <span class="text-primary-400 ml-1">TTC</span>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="space-y-3 mb-6">
                <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide px-1">Choisissez votre moyen de paiement</h3>

                <!-- Carte bancaire -->
                <label
                    class="flex items-center bg-white rounded-2xl shadow-sm p-4 cursor-pointer transition-all duration-200 border-2"
                    :class="paymentMethod === 'card' ? 'border-primary-500 shadow-lg shadow-primary-100/50' : 'border-transparent hover:shadow-md'"
                >
                    <div class="relative">
                        <input type="radio" value="card" v-model="paymentMethod" class="sr-only peer" />
                        <div class="w-6 h-6 border-2 border-gray-300 rounded-full peer-checked:border-primary-600 peer-checked:border-[6px] transition-all duration-200"></div>
                    </div>
                    <div class="ml-4 flex items-center flex-1">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-3 shadow-sm">
                            <CreditCardIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-900">Carte bancaire</span>
                            <p class="text-xs text-gray-400">Visa, Mastercard, Amex</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1">
                        <div class="w-10 h-6 bg-[#1A1F71] rounded flex items-center justify-center">
                            <span class="text-white text-[8px] font-bold">VISA</span>
                        </div>
                        <div class="w-10 h-6 bg-gradient-to-r from-[#EB001B] to-[#F79E1B] rounded flex items-center justify-center">
                            <div class="flex">
                                <div class="w-3 h-3 bg-[#EB001B] rounded-full -mr-1"></div>
                                <div class="w-3 h-3 bg-[#F79E1B] rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </label>

                <!-- Bancontact -->
                <label
                    class="flex items-center bg-white rounded-2xl shadow-sm p-4 cursor-pointer transition-all duration-200 border-2"
                    :class="paymentMethod === 'bancontact' ? 'border-[#005498] shadow-lg shadow-blue-100/50' : 'border-transparent hover:shadow-md'"
                >
                    <div class="relative">
                        <input type="radio" value="bancontact" v-model="paymentMethod" class="sr-only peer" />
                        <div class="w-6 h-6 border-2 border-gray-300 rounded-full peer-checked:border-[#005498] peer-checked:border-[6px] transition-all duration-200"></div>
                    </div>
                    <div class="ml-4 flex items-center flex-1">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#005498] to-[#003d6e] rounded-xl flex items-center justify-center mr-3 shadow-sm">
                            <span class="text-white text-lg font-bold">B</span>
                        </div>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-900">Bancontact</span>
                            <p class="text-xs text-gray-400">Paiement belge securise</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">Populaire</span>
                </label>

                <!-- Apple Pay / Google Pay -->
                <label
                    v-if="canUseWallet"
                    class="flex items-center bg-white rounded-2xl shadow-sm p-4 cursor-pointer transition-all duration-200 border-2"
                    :class="paymentMethod === 'wallet' ? 'border-gray-900 shadow-lg' : 'border-transparent hover:shadow-md'"
                >
                    <div class="relative">
                        <input type="radio" value="wallet" v-model="paymentMethod" class="sr-only peer" />
                        <div class="w-6 h-6 border-2 border-gray-300 rounded-full peer-checked:border-gray-900 peer-checked:border-[6px] transition-all duration-200"></div>
                    </div>
                    <div class="ml-4 flex items-center flex-1">
                        <div class="w-12 h-12 bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl flex items-center justify-center mr-3 shadow-sm">
                            <DevicePhoneMobileIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-900">Apple Pay / Google Pay</span>
                            <p class="text-xs text-gray-400">Paiement rapide et securise</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded-full">Rapide</span>
                </label>

                <!-- PayPal -->
                <label
                    class="flex items-center bg-white rounded-2xl shadow-sm p-4 cursor-pointer transition-all duration-200 border-2"
                    :class="paymentMethod === 'paypal' ? 'border-[#003087] shadow-lg shadow-blue-100/50' : 'border-transparent hover:shadow-md'"
                >
                    <div class="relative">
                        <input type="radio" value="paypal" v-model="paymentMethod" class="sr-only peer" />
                        <div class="w-6 h-6 border-2 border-gray-300 rounded-full peer-checked:border-[#003087] peer-checked:border-[6px] transition-all duration-200"></div>
                    </div>
                    <div class="ml-4 flex items-center flex-1">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#003087] to-[#001f5c] rounded-xl flex items-center justify-center mr-3 shadow-sm">
                            <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .757-.632h6.927c2.3 0 4.145.562 5.342 1.593 1.133.97 1.605 2.419 1.363 4.188-.262 1.932-1.047 3.485-2.335 4.62-1.307 1.152-3.02 1.74-5.096 1.74h-2.21a.77.77 0 0 0-.758.632l-.87 5.476z"/></svg>
                        </div>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-900">PayPal</span>
                            <p class="text-xs text-gray-400">Payez avec votre compte PayPal</p>
                        </div>
                    </div>
                </label>

                <!-- Virement bancaire -->
                <label
                    class="flex items-center bg-white rounded-2xl shadow-sm p-4 cursor-pointer transition-all duration-200 border-2"
                    :class="paymentMethod === 'bank_transfer' ? 'border-amber-500 shadow-lg shadow-amber-100/50' : 'border-transparent hover:shadow-md'"
                >
                    <div class="relative">
                        <input type="radio" value="bank_transfer" v-model="paymentMethod" class="sr-only peer" />
                        <div class="w-6 h-6 border-2 border-gray-300 rounded-full peer-checked:border-amber-500 peer-checked:border-[6px] transition-all duration-200"></div>
                    </div>
                    <div class="ml-4 flex items-center flex-1">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center mr-3 shadow-sm">
                            <BuildingLibraryIcon class="w-6 h-6 text-white" />
                        </div>
                        <div class="flex-1">
                            <span class="font-semibold text-gray-900">Virement bancaire</span>
                            <p class="text-xs text-gray-400">Delai de traitement: 2-3 jours</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full">2-3 jours</span>
                </label>
            </div>

            <!-- Stripe Card Element -->
            <Transition name="fade">
            <div v-if="paymentMethod === 'card'" class="bg-white rounded-2xl shadow-sm p-5 mb-4 border border-gray-100">
                <div class="flex items-center mb-4">
                    <LockClosedIcon class="w-5 h-5 text-green-500 mr-2" />
                    <span class="text-sm text-gray-600">Paiement securise par Stripe</span>
                </div>
                <StripeCardElement
                    v-if="stripeKey"
                    ref="stripeCardRef"
                    :stripe-key="stripeKey"
                    @ready="stripeReady = true"
                    @change="onCardChange"
                    @complete="cardComplete = true"
                />
            </div>
            </Transition>

            <!-- Payment Info Messages -->
            <Transition name="fade">
            <div v-if="paymentMethod === 'bancontact'" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-5 mb-4 border border-blue-100">
                <div class="flex items-start">
                    <InformationCircleIcon class="w-6 h-6 text-blue-500 mr-3 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-blue-700">Vous serez redirige vers votre application Bancontact ou votre banque pour confirmer le paiement de maniere securisee.</p>
                </div>
            </div>
            </Transition>

            <Transition name="fade">
            <div v-if="paymentMethod === 'wallet'" class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-2xl p-5 mb-4 border border-gray-200">
                <div class="flex items-start">
                    <InformationCircleIcon class="w-6 h-6 text-gray-500 mr-3 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-gray-700">Utilisez Apple Pay ou Google Pay pour un paiement rapide, securise et sans contact.</p>
                </div>
            </div>
            </Transition>

            <Transition name="fade">
            <div v-if="paymentMethod === 'paypal'" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-5 mb-4 border border-blue-100">
                <div class="flex items-start">
                    <InformationCircleIcon class="w-6 h-6 text-blue-500 mr-3 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-blue-700">Vous serez redirige vers PayPal pour finaliser votre paiement en toute securite.</p>
                </div>
            </div>
            </Transition>

            <Transition name="fade">
            <div v-if="paymentMethod === 'bank_transfer'" class="bg-gradient-to-r from-amber-50 to-yellow-50 rounded-2xl p-5 mb-4 border border-amber-200">
                <div class="flex items-start">
                    <ExclamationTriangleIcon class="w-6 h-6 text-amber-500 mr-3 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm text-amber-800 font-medium mb-1">Reservation en attente de paiement</p>
                        <p class="text-xs text-amber-700">Votre box sera reserve pendant 48h. La confirmation sera effective apres reception du virement (2-3 jours ouvrables).</p>
                    </div>
                </div>
            </div>
            </Transition>

            <!-- Error -->
            <Transition name="fade">
            <div v-if="errorMessage" class="bg-gradient-to-r from-red-50 to-rose-50 rounded-2xl p-5 mb-4 border border-red-200">
                <div class="flex items-start">
                    <XCircleIcon class="w-6 h-6 text-red-500 mr-3 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-red-700 font-medium">{{ errorMessage }}</p>
                </div>
            </div>
            </Transition>

            <!-- Security Badge -->
            <div class="flex items-center justify-center py-4">
                <LockClosedIcon class="w-4 h-4 text-gray-400 mr-2" />
                <span class="text-xs text-gray-400">Paiement 100% securise - Donnees chiffrees SSL</span>
            </div>
        </div>
        </Transition>

        <!-- Navigation Buttons -->
        <div class="fixed bottom-20 left-0 right-0 px-4 pb-4 bg-gradient-to-t from-gray-50 via-gray-50 to-transparent pt-6">
            <div class="flex space-x-3 max-w-lg mx-auto">
                <button
                    v-if="step > 1"
                    @click="previousStep"
                    class="flex-1 py-4 bg-white text-gray-700 font-semibold rounded-2xl shadow-sm border border-gray-200 hover:bg-gray-50 hover:shadow-md active:scale-[0.98] transition-all duration-200 flex items-center justify-center"
                >
                    <ArrowLeftIcon class="w-5 h-5 mr-2" />
                    Retour
                </button>
                <button
                    @click="nextStep"
                    :disabled="!canProceed || processing"
                    class="flex-1 py-4 bg-gradient-to-r from-primary-500 to-primary-600 text-white font-semibold rounded-2xl shadow-lg shadow-primary-200 disabled:opacity-50 disabled:shadow-none hover:shadow-xl hover:shadow-primary-300 active:scale-[0.98] transition-all duration-200 flex items-center justify-center"
                >
                    <span v-if="processing" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Traitement en cours...
                    </span>
                    <span v-else class="flex items-center">
                        {{ paymentButtonText }}
                        <ArrowRightIcon v-if="step < 5" class="w-5 h-5 ml-2" />
                        <LockClosedIcon v-else class="w-5 h-5 ml-2" />
                    </span>
                </button>
            </div>
        </div>

        <div class="h-36"></div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import StripeCardElement from '@/Components/Payment/StripeCardElement.vue'
import {
    MagnifyingGlassIcon,
    MapPinIcon,
    BuildingOfficeIcon,
    CubeIcon,
    CheckIcon,
    CreditCardIcon,
    DevicePhoneMobileIcon,
    BuildingLibraryIcon,
    CalendarDaysIcon,
    ShieldCheckIcon,
    ShieldExclamationIcon,
    ClipboardDocumentCheckIcon,
    BanknotesIcon,
    HomeIcon,
    LockClosedIcon,
    InformationCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: Array,
    customer: Object,
    stripeKey: String,
})

const step = ref(1)
const steps = ['Site', 'Box', 'Options', 'Resume', 'Paiement']

// Step 1
const siteSearch = ref('')
const selectedSite = ref(null)

// Step 2
const selectedSize = ref('all')
const selectedBox = ref(null)

// Step 3
const startDate = ref(new Date().toISOString().split('T')[0])
const includeDeposit = ref(true)
const includeInsurance = ref(false)

// Step 4
const acceptTerms = ref(false)

// Step 5
const paymentMethod = ref('card')
const stripeCardRef = ref(null)
const stripeReady = ref(false)
const cardComplete = ref(false)
const processing = ref(false)
const errorMessage = ref(null)

const sizeFilters = [
    { value: 'all', label: 'Tous' },
    { value: 'small', label: '1-5 m³' },
    { value: 'medium', label: '5-15 m³' },
    { value: 'large', label: '15+ m³' },
]

// =====================================================
// LocalStorage Persistence - Sauvegarde automatique
// =====================================================
const STORAGE_KEY = 'boxibox_reserve_progress'

// Restaurer l'etat depuis localStorage au montage
onMounted(() => {
    try {
        const saved = localStorage.getItem(STORAGE_KEY)
        if (saved) {
            const data = JSON.parse(saved)
            // Verifier que les donnees ne sont pas trop anciennes (expire apres 1 heure)
            if (data.timestamp && Date.now() - data.timestamp < 60 * 60 * 1000) {
                step.value = data.step || 1
                siteSearch.value = data.siteSearch || ''
                selectedSize.value = data.selectedSize || 'all'
                startDate.value = data.startDate || new Date().toISOString().split('T')[0]
                includeDeposit.value = data.includeDeposit ?? true
                includeInsurance.value = data.includeInsurance ?? false
                paymentMethod.value = data.paymentMethod || 'card'

                // Restaurer le site selectionne
                if (data.selectedSiteId && props.sites) {
                    const site = props.sites.find(s => s.id === data.selectedSiteId)
                    if (site) {
                        selectedSite.value = site
                        // Restaurer la box selectionnee
                        if (data.selectedBoxId && site.boxes) {
                            const box = site.boxes.find(b => b.id === data.selectedBoxId)
                            if (box && box.status === 'available') {
                                selectedBox.value = box
                            }
                        }
                    }
                }
            } else {
                // Donnees expirees, supprimer
                localStorage.removeItem(STORAGE_KEY)
            }
        }
    } catch (e) {
        console.error('Erreur restauration localStorage:', e)
        localStorage.removeItem(STORAGE_KEY)
    }
})

// Sauvegarder l'etat dans localStorage a chaque changement
watch(
    [step, siteSearch, selectedSite, selectedBox, selectedSize, startDate, includeDeposit, includeInsurance, paymentMethod],
    () => {
        try {
            const data = {
                timestamp: Date.now(),
                step: step.value,
                siteSearch: siteSearch.value,
                selectedSiteId: selectedSite.value?.id,
                selectedBoxId: selectedBox.value?.id,
                selectedSize: selectedSize.value,
                startDate: startDate.value,
                includeDeposit: includeDeposit.value,
                includeInsurance: includeInsurance.value,
                paymentMethod: paymentMethod.value,
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
        } catch (e) {
            console.error('Erreur sauvegarde localStorage:', e)
        }
    },
    { deep: true }
)

// Nettoyer localStorage apres une reservation reussie
const clearReservationProgress = () => {
    localStorage.removeItem(STORAGE_KEY)
}
// =====================================================

const minStartDate = computed(() => new Date().toISOString().split('T')[0])

const filteredSites = computed(() => {
    if (!siteSearch.value) return props.sites || []
    const query = siteSearch.value.toLowerCase()
    return (props.sites || []).filter(s =>
        s.name?.toLowerCase().includes(query) ||
        s.city?.toLowerCase().includes(query)
    )
})

const filteredBoxes = computed(() => {
    if (!selectedSite.value?.boxes) return []
    let boxes = selectedSite.value.boxes.filter(b => b.status === 'available')

    if (selectedSize.value !== 'all') {
        boxes = boxes.filter(b => {
            const vol = b.volume
            switch (selectedSize.value) {
                case 'small': return vol >= 1 && vol < 5
                case 'medium': return vol >= 5 && vol < 15
                case 'large': return vol >= 15
                default: return true
            }
        })
    }
    return boxes
})

// Calculate total with correct tax rates (matching backend - FISCAL COMPLIANCE)
// - Storage/rent: 20% VAT
// - Deposit: 0% VAT (security deposit, not taxable)
// - Insurance: 9% tax
const totalAmount = computed(() => {
    if (!selectedBox.value) return 0
    let total = Math.round(selectedBox.value.monthly_price * 1.20 * 100) / 100 // 20% VAT on storage
    if (includeDeposit.value) total += selectedBox.value.monthly_price * 2 // No VAT on deposit
    if (includeInsurance.value) total += Math.round(15 * 1.09 * 100) / 100 // 9% tax on insurance
    return Math.round(total * 100) / 100
})

// Monthly price with VAT
const monthlyPriceTTC = computed(() => {
    if (!selectedBox.value) return 0
    return Math.round(selectedBox.value.monthly_price * 1.20 * 100) / 100
})

// Insurance with tax
const insuranceTTC = computed(() => Math.round(15 * 1.09 * 100) / 100)

// Check if Apple Pay / Google Pay is available
const canUseWallet = ref(false)
const checkWalletAvailability = async () => {
    if (typeof window !== 'undefined' && window.PaymentRequest) {
        try {
            const supportedMethods = [{ supportedMethods: 'https://apple.com/apple-pay' }]
            const paymentDetails = { total: { label: 'Test', amount: { currency: 'EUR', value: '1.00' } } }
            const request = new PaymentRequest(supportedMethods, paymentDetails)
            canUseWallet.value = await request.canMakePayment()
        } catch {
            canUseWallet.value = false
        }
    }
}
checkWalletAvailability()

const canProceed = computed(() => {
    switch (step.value) {
        case 1: return selectedSite.value !== null
        case 2: return selectedBox.value !== null
        case 3: return startDate.value
        case 4: return acceptTerms.value
        case 5:
            if (paymentMethod.value === 'card') return stripeReady.value && cardComplete.value
            // Bancontact, PayPal, Wallet, Bank Transfer can always proceed
            return true
        default: return false
    }
})

// Button text based on payment method
const paymentButtonText = computed(() => {
    if (step.value !== 5) return step.value === 4 ? 'Passer au paiement' : 'Continuer'

    switch (paymentMethod.value) {
        case 'paypal': return 'Payer avec PayPal'
        case 'bancontact': return 'Payer avec Bancontact'
        case 'wallet': return 'Payer avec Apple/Google Pay'
        case 'bank_transfer': return 'Confirmer la reservation'
        default: return `Payer ${totalAmount.value.toFixed(2)}€`
    }
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

const selectSite = (site) => { selectedSite.value = site }
const selectBox = (box) => { selectedBox.value = box }

// Helper function for box size indicator bars
const getBoxSizeClass = (volume, level) => {
    const baseHeight = level === 1 ? 'h-3' : level === 2 ? 'h-4' : 'h-5'
    return baseHeight
}

const onCardChange = (data) => { cardComplete.value = data.complete }

const previousStep = () => { if (step.value > 1) step.value-- }

const nextStep = async () => {
    if (step.value < 5) {
        step.value++
    } else {
        await processPayment()
    }
}

const processPayment = async () => {
    processing.value = true
    errorMessage.value = null

    try {
        switch (paymentMethod.value) {
            case 'paypal':
                await processPayPal()
                break
            case 'bancontact':
                await processBancontact()
                break
            case 'wallet':
                await processWallet()
                break
            case 'bank_transfer':
                await processBankTransfer()
                break
            default: // card
                await processStripe()
        }
    } catch (error) {
        errorMessage.value = error.message || 'Erreur de paiement'
        processing.value = false
    }
}

const processStripe = async () => {
    // Create payment intent
    const intentResponse = await axios.post(route('mobile.reserve.payment-intent'), {
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        include_deposit: includeDeposit.value,
        include_insurance: includeInsurance.value,
    })

    const { clientSecret, reservationToken } = intentResponse.data

    // Confirm payment
    const stripeCard = stripeCardRef.value
    const paymentIntent = await stripeCard.confirmPayment(clientSecret)

    // Confirm reservation
    const confirmResponse = await axios.post(route('mobile.reserve.confirm'), {
        payment_intent_id: paymentIntent.id,
        reservation_token: reservationToken,
    })

    if (confirmResponse.data.success) {
        clearReservationProgress()
        router.visit(confirmResponse.data.redirect)
    } else {
        throw new Error(confirmResponse.data.error)
    }
}

const processPayPal = async () => {
    const response = await axios.post(route('mobile.reserve.paypal.create'), {
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        include_deposit: includeDeposit.value,
        include_insurance: includeInsurance.value,
    })

    if (response.data.approvalUrl) {
        window.location.href = response.data.approvalUrl
    } else {
        throw new Error('Erreur PayPal')
    }
}

const processBancontact = async () => {
    // Create Bancontact payment session via Stripe
    const response = await axios.post(route('mobile.reserve.bancontact.create'), {
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        include_deposit: includeDeposit.value,
        include_insurance: includeInsurance.value,
    })

    if (response.data.redirectUrl) {
        window.location.href = response.data.redirectUrl
    } else {
        throw new Error('Erreur Bancontact')
    }
}

const processWallet = async () => {
    // Create wallet payment via Stripe Payment Request
    const response = await axios.post(route('mobile.reserve.wallet.create'), {
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        include_deposit: includeDeposit.value,
        include_insurance: includeInsurance.value,
    })

    if (response.data.clientSecret) {
        // Use Stripe's Payment Request API
        const stripe = window.Stripe(props.stripeKey)
        const { error } = await stripe.confirmCardPayment(response.data.clientSecret, {
            payment_method: response.data.paymentMethodId,
        })

        if (error) {
            throw new Error(error.message)
        }

        // Confirm reservation
        const confirmResponse = await axios.post(route('mobile.reserve.confirm'), {
            payment_intent_id: response.data.paymentIntentId,
            reservation_token: response.data.reservationToken,
        })

        if (confirmResponse.data.success) {
            clearReservationProgress()
            router.visit(confirmResponse.data.redirect)
        } else {
            throw new Error(confirmResponse.data.error)
        }
    } else {
        throw new Error('Erreur Wallet')
    }
}

const processBankTransfer = async () => {
    // Create pending reservation with bank transfer payment
    const response = await axios.post(route('mobile.reserve.bank-transfer.create'), {
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        include_deposit: includeDeposit.value,
        include_insurance: includeInsurance.value,
    })

    if (response.data.success) {
        clearReservationProgress()
        router.visit(response.data.redirect)
    } else {
        throw new Error(response.data.error || 'Erreur lors de la reservation')
    }
}
</script>

<style scoped>
/* Slide fade transition for steps */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.slide-fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

/* Fade transition for payment info boxes */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Hide scrollbar for size filters */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Custom toggle switch animation */
.peer:checked ~ div::after {
    transform: translateX(100%);
}
</style>
