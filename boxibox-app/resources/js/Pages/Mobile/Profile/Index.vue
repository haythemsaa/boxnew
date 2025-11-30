<template>
    <MobileLayout title="Mon Profil" :show-back="true">
        <!-- Profile Header -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="bg-gradient-to-br from-primary-500 to-primary-700 p-6 text-white text-center">
                <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur">
                    <span class="text-4xl font-bold">{{ userInitial }}</span>
                </div>
                <h2 class="text-xl font-bold">{{ customer?.first_name }} {{ customer?.last_name }}</h2>
                <p class="text-primary-200 mt-1">{{ customer?.email }}</p>
                <span class="inline-block mt-2 px-3 py-1 bg-white/20 rounded-full text-sm">
                    Client depuis {{ memberSince }}
                </span>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Informations personnelles</h3>
                <button @click="editMode = !editMode" class="text-primary-600 text-sm font-medium">
                    {{ editMode ? 'Annuler' : 'Modifier' }}
                </button>
            </div>

            <form @submit.prevent="saveProfile" class="space-y-4">
                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Type de compte</label>
                    <p class="text-gray-900 font-medium">{{ customer?.type === 'company' ? 'Professionnel' : 'Particulier' }}</p>
                </div>

                <!-- Company Name (if company) -->
                <div v-if="customer?.type === 'company'">
                    <label class="block text-sm font-medium text-gray-500 mb-1">Raison sociale</label>
                    <input
                        v-if="editMode"
                        v-model="form.company_name"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                    <p v-else class="text-gray-900">{{ customer?.company_name }}</p>
                </div>

                <!-- First Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Prenom</label>
                    <input
                        v-if="editMode"
                        v-model="form.first_name"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                    <p v-else class="text-gray-900">{{ customer?.first_name }}</p>
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Nom</label>
                    <input
                        v-if="editMode"
                        v-model="form.last_name"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                    <p v-else class="text-gray-900">{{ customer?.last_name }}</p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                    <input
                        v-if="editMode"
                        v-model="form.email"
                        type="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                    <p v-else class="text-gray-900">{{ customer?.email }}</p>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Telephone</label>
                    <input
                        v-if="editMode"
                        v-model="form.phone"
                        type="tel"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                    <p v-else class="text-gray-900">{{ customer?.phone || '-' }}</p>
                </div>

                <!-- Save Button -->
                <button
                    v-if="editMode"
                    type="submit"
                    :disabled="saving"
                    class="w-full py-3 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
            </form>
        </div>

        <!-- Address -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Adresse de facturation</h3>
                <button @click="editAddressMode = !editAddressMode" class="text-primary-600 text-sm font-medium">
                    {{ editAddressMode ? 'Annuler' : 'Modifier' }}
                </button>
            </div>

            <form @submit.prevent="saveAddress" class="space-y-4">
                <!-- Address -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Adresse</label>
                    <input
                        v-if="editAddressMode"
                        v-model="addressForm.address"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                    <p v-else class="text-gray-900">{{ customer?.address || '-' }}</p>
                </div>

                <!-- Postal Code & City -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Code postal</label>
                        <input
                            v-if="editAddressMode"
                            v-model="addressForm.postal_code"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                        />
                        <p v-else class="text-gray-900">{{ customer?.postal_code || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Ville</label>
                        <input
                            v-if="editAddressMode"
                            v-model="addressForm.city"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                        />
                        <p v-else class="text-gray-900">{{ customer?.city || '-' }}</p>
                    </div>
                </div>

                <!-- Country -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Pays</label>
                    <input
                        v-if="editAddressMode"
                        v-model="addressForm.country"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                    <p v-else class="text-gray-900">{{ customer?.country || 'France' }}</p>
                </div>

                <!-- Save Button -->
                <button
                    v-if="editAddressMode"
                    type="submit"
                    :disabled="savingAddress"
                    class="w-full py-3 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                >
                    {{ savingAddress ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
            </form>
        </div>

        <!-- Security -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Securite</h3>

            <div class="space-y-3">
                <button
                    @click="showPasswordModal = true"
                    class="w-full flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100"
                >
                    <div class="flex items-center">
                        <LockClosedIcon class="w-5 h-5 text-gray-400 mr-3" />
                        <span class="text-gray-900">Changer le mot de passe</span>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </button>

                <button
                    @click="showDeleteModal = true"
                    class="w-full flex items-center justify-between p-4 bg-red-50 rounded-xl hover:bg-red-100"
                >
                    <div class="flex items-center">
                        <TrashIcon class="w-5 h-5 text-red-500 mr-3" />
                        <span class="text-red-600">Supprimer mon compte</span>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-red-400" />
                </button>
            </div>
        </div>

        <!-- Password Change Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showPasswordModal" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" @click="showPasswordModal = false"></div>
                <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-xl">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Changer le mot de passe</h3>

                        <form @submit.prevent="changePassword" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe actuel</label>
                                <input
                                    v-model="passwordForm.current_password"
                                    type="password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                                <input
                                    v-model="passwordForm.new_password"
                                    type="password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                                <input
                                    v-model="passwordForm.confirm_password"
                                    type="password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                />
                            </div>

                            <div class="flex space-x-3 pt-2">
                                <button
                                    type="button"
                                    @click="showPasswordModal = false"
                                    class="flex-1 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="changingPassword"
                                    class="flex-1 py-3 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                                >
                                    {{ changingPassword ? 'Modification...' : 'Modifier' }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="h-8 bg-white"></div>
                </div>
            </div>
        </Transition>

        <!-- Delete Account Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showDeleteModal" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-xl">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
                    <div class="p-6">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <ExclamationTriangleIcon class="w-8 h-8 text-red-600" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Supprimer mon compte ?</h3>
                        <p class="text-gray-500 text-center mb-6">
                            Cette action est irreversible. Toutes vos donnees seront supprimees.
                        </p>

                        <div class="flex space-x-3">
                            <button
                                @click="showDeleteModal = false"
                                class="flex-1 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl"
                            >
                                Annuler
                            </button>
                            <button
                                @click="deleteAccount"
                                class="flex-1 py-3 bg-red-600 text-white font-semibold rounded-xl"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>
                    <div class="h-8 bg-white"></div>
                </div>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    LockClosedIcon,
    TrashIcon,
    ChevronRightIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customer: Object,
})

const page = usePage()

const editMode = ref(false)
const editAddressMode = ref(false)
const saving = ref(false)
const savingAddress = ref(false)
const showPasswordModal = ref(false)
const showDeleteModal = ref(false)
const changingPassword = ref(false)

const form = ref({
    first_name: props.customer?.first_name || '',
    last_name: props.customer?.last_name || '',
    email: props.customer?.email || '',
    phone: props.customer?.phone || '',
    company_name: props.customer?.company_name || '',
})

const addressForm = ref({
    address: props.customer?.address || '',
    postal_code: props.customer?.postal_code || '',
    city: props.customer?.city || '',
    country: props.customer?.country || 'France',
})

const passwordForm = ref({
    current_password: '',
    new_password: '',
    confirm_password: '',
})

const userInitial = computed(() => {
    return props.customer?.first_name?.charAt(0)?.toUpperCase() || 'U'
})

const memberSince = computed(() => {
    if (!props.customer?.created_at) return '-'
    const date = new Date(props.customer.created_at)
    return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

const saveProfile = () => {
    saving.value = true
    router.put(route('mobile.profile.update'), form.value, {
        onFinish: () => {
            saving.value = false
            editMode.value = false
        },
    })
}

const saveAddress = () => {
    savingAddress.value = true
    router.put(route('mobile.profile.update-address'), addressForm.value, {
        onFinish: () => {
            savingAddress.value = false
            editAddressMode.value = false
        },
    })
}

const changePassword = () => {
    if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
        alert('Les mots de passe ne correspondent pas')
        return
    }

    changingPassword.value = true
    router.put(route('mobile.profile.change-password'), passwordForm.value, {
        onFinish: () => {
            changingPassword.value = false
            showPasswordModal.value = false
            passwordForm.value = { current_password: '', new_password: '', confirm_password: '' }
        },
    })
}

const deleteAccount = () => {
    if (confirm('Etes-vous vraiment sur de vouloir supprimer votre compte ?')) {
        router.delete(route('mobile.profile.delete'))
    }
}
</script>
