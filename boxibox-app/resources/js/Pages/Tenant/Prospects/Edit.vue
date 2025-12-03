<template>
    <TenantLayout :title="`Modifier ${prospect.first_name} ${prospect.last_name}`">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-rose-50 to-pink-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.prospects.index')"
                        class="inline-flex items-center text-sm text-rose-600 hover:text-rose-800 transition-colors mb-4"
                    >
                        <ArrowLeftIcon class="w-5 h-5 mr-2" />
                        Retour aux prospects
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-lg shadow-rose-500/25">
                            <UserIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Modifier le prospect</h1>
                            <p class="text-gray-500 mt-1">
                                {{ prospect.type === 'company' ? prospect.company_name : `${prospect.first_name} ${prospect.last_name}` }}
                            </p>
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
                                    <CheckIcon v-if="currentStep > step.number" class="w-6 h-6" />
                                    <component v-else :is="step.icon" class="w-6 h-6" />
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
                        <!-- Step 1: Statut & Type -->
                        <div v-show="currentStep === 1" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center">
                                    <FlagIcon class="w-5 h-5 text-rose-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Statut & Type</h3>
                                    <p class="text-sm text-gray-500">Mettez à jour le statut du prospect</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Statut -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Statut <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
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
                                                'peer-checked:border-rose-500 peer-checked:bg-rose-50',
                                                'hover:border-rose-300 hover:bg-rose-50/50',
                                                form.status !== status.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="['w-8 h-8 rounded-lg mx-auto mb-1 flex items-center justify-center', status.bgColor]">
                                                    <component :is="status.icon" class="w-4 h-4" :class="status.iconColor" />
                                                </div>
                                                <span class="font-medium text-gray-900 text-xs">{{ status.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

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
                                                        <component :is="type.icon" class="w-6 h-6" :class="type.iconColor" />
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
                                <div v-if="form.type === 'company'" class="space-y-4 bg-purple-50 rounded-xl p-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Nom de l'entreprise <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <BuildingOfficeIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <input
                                                v-model="form.company_name"
                                                type="text"
                                                class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all bg-white"
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
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all bg-white"
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
                                    <EnvelopeIcon class="w-5 h-5 text-pink-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Coordonnées</h3>
                                    <p class="text-sm text-gray-500">Informations de contact du prospect</p>
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
                                                <EnvelopeIcon class="w-5 h-5 text-gray-400" />
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
                                                <PhoneIcon class="w-5 h-5 text-gray-400" />
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
                                    <ClipboardDocumentListIcon class="w-5 h-5 text-purple-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Détails du Projet</h3>
                                    <p class="text-sm text-gray-500">Besoins et budget du prospect</p>
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
                                                    <component :is="source.icon" class="w-4 h-4" :class="source.iconColor" />
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
                                                <CurrencyEuroIcon class="w-5 h-5 text-gray-400" />
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
                                    <CheckCircleIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Récapitulatif</h3>
                                    <p class="text-sm text-gray-500">Vérifiez les informations avant d'enregistrer</p>
                                </div>
                            </div>

                            <!-- Historique -->
                            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                                <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                    <ClockIcon class="w-5 h-5 mr-2 text-gray-500" />
                                    Historique
                                </h4>
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="text-center p-3 bg-white rounded-lg">
                                        <p class="text-2xl font-bold text-rose-600">{{ prospect.follow_up_count }}</p>
                                        <p class="text-xs text-gray-500">Relances</p>
                                    </div>
                                    <div class="text-center p-3 bg-white rounded-lg">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ prospect.last_contact_at ? formatDate(prospect.last_contact_at) : 'Jamais' }}
                                        </p>
                                        <p class="text-xs text-gray-500">Dernier contact</p>
                                    </div>
                                    <div class="text-center p-3 bg-white rounded-lg">
                                        <p class="text-sm font-semibold text-gray-900">{{ formatDate(prospect.created_at) }}</p>
                                        <p class="text-xs text-gray-500">Création</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-rose-50 to-pink-50 rounded-xl p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Identité -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-rose-100 rounded-lg flex items-center justify-center">
                                                <UserIcon class="w-4 h-4 text-rose-600" />
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
                                            <p class="mt-2">
                                                <span :class="selectedStatusConfig?.color" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                                    {{ selectedStatusConfig?.label }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Contact -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                                                <EnvelopeIcon class="w-4 h-4 text-pink-600" />
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
                                                <ClipboardDocumentListIcon class="w-4 h-4 text-purple-600" />
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
                                                <CurrencyEuroIcon class="w-4 h-4 text-emerald-600" />
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
                                <ChevronLeftIcon class="w-5 h-5 mr-2" />
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
                                    <ChevronRightIcon class="w-5 h-5 ml-2" />
                                </button>
                                <button
                                    v-else
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ArrowPathIcon v-if="form.processing" class="animate-spin w-5 h-5 mr-2" />
                                    <CheckIcon v-else class="w-5 h-5 mr-2" />
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
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
import {
    ArrowLeftIcon,
    UserIcon,
    BuildingOfficeIcon,
    EnvelopeIcon,
    PhoneIcon,
    ClipboardDocumentListIcon,
    CheckIcon,
    CheckCircleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ArrowPathIcon,
    CurrencyEuroIcon,
    FlagIcon,
    ClockIcon,
    GlobeAltIcon,
    PhoneArrowDownLeftIcon,
    AtSymbolIcon,
    UsersIcon,
    MapPinIcon,
    ShareIcon,
    QuestionMarkCircleIcon,
    SparklesIcon,
    ChatBubbleLeftIcon,
    DocumentCheckIcon,
    UserPlusIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    prospect: Object,
})

const currentStep = ref(1)

const steps = [
    { number: 1, title: 'Statut', icon: FlagIcon },
    { number: 2, title: 'Contact', icon: EnvelopeIcon },
    { number: 3, title: 'Projet', icon: ClipboardDocumentListIcon },
    { number: 4, title: 'Récapitulatif', icon: CheckCircleIcon }
]

const statuses = [
    { value: 'new', label: 'Nouveau', icon: SparklesIcon, bgColor: 'bg-blue-100', iconColor: 'text-blue-600', color: 'bg-blue-100 text-blue-700' },
    { value: 'contacted', label: 'Contacté', icon: ChatBubbleLeftIcon, bgColor: 'bg-amber-100', iconColor: 'text-amber-600', color: 'bg-amber-100 text-amber-700' },
    { value: 'qualified', label: 'Qualifié', icon: DocumentCheckIcon, bgColor: 'bg-purple-100', iconColor: 'text-purple-600', color: 'bg-purple-100 text-purple-700' },
    { value: 'quoted', label: 'Devis', icon: ClipboardDocumentListIcon, bgColor: 'bg-indigo-100', iconColor: 'text-indigo-600', color: 'bg-indigo-100 text-indigo-700' },
    { value: 'converted', label: 'Converti', icon: UserPlusIcon, bgColor: 'bg-emerald-100', iconColor: 'text-emerald-600', color: 'bg-emerald-100 text-emerald-700' },
    { value: 'lost', label: 'Perdu', icon: XCircleIcon, bgColor: 'bg-red-100', iconColor: 'text-red-600', color: 'bg-red-100 text-red-700' },
]

const prospectTypes = [
    { value: 'individual', label: 'Particulier', description: 'Client individuel', icon: UserIcon, bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
    { value: 'company', label: 'Entreprise', description: 'Client professionnel', icon: BuildingOfficeIcon, bgColor: 'bg-purple-100', iconColor: 'text-purple-600' }
]

const sources = [
    { value: 'website', label: 'Site web', icon: GlobeAltIcon, bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
    { value: 'phone', label: 'Téléphone', icon: PhoneArrowDownLeftIcon, bgColor: 'bg-green-100', iconColor: 'text-green-600' },
    { value: 'email', label: 'Email', icon: AtSymbolIcon, bgColor: 'bg-amber-100', iconColor: 'text-amber-600' },
    { value: 'referral', label: 'Parrainage', icon: UsersIcon, bgColor: 'bg-purple-100', iconColor: 'text-purple-600' },
    { value: 'walk_in', label: 'Visite', icon: MapPinIcon, bgColor: 'bg-rose-100', iconColor: 'text-rose-600' },
    { value: 'social_media', label: 'Réseaux', icon: ShareIcon, bgColor: 'bg-pink-100', iconColor: 'text-pink-600' },
    { value: 'other', label: 'Autre', icon: QuestionMarkCircleIcon, bgColor: 'bg-gray-100', iconColor: 'text-gray-600' }
]

const boxSizes = [
    { value: '1-5m²', label: '1-5 m²' },
    { value: '5-10m²', label: '5-10 m²' },
    { value: '10-20m²', label: '10-20 m²' },
    { value: '20-50m²', label: '20-50 m²' },
    { value: '+50m²', label: '+50 m²' }
]

const form = useForm({
    type: props.prospect.type,
    first_name: props.prospect.first_name,
    last_name: props.prospect.last_name,
    company_name: props.prospect.company_name || '',
    siret: props.prospect.siret || '',
    email: props.prospect.email,
    phone: props.prospect.phone || '',
    address: props.prospect.address || '',
    postal_code: props.prospect.postal_code || '',
    city: props.prospect.city || '',
    status: props.prospect.status,
    source: props.prospect.source,
    box_size_interested: props.prospect.box_size_interested || '',
    move_in_date: props.prospect.move_in_date ? props.prospect.move_in_date.split('T')[0] : '',
    budget: props.prospect.budget || '',
    notes: props.prospect.notes || '',
})

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        if (form.type === 'company') {
            return form.first_name && form.last_name && form.company_name && form.status
        }
        return form.first_name && form.last_name && form.status
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

const selectedStatusConfig = computed(() => {
    return statuses.find(s => s.value === form.status)
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

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
    form.put(route('tenant.prospects.update', props.prospect.id))
}
</script>
