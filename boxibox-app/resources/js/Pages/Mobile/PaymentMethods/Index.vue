<template>
    <MobileLayout title="Moyens de paiement" :show-back="true">
        <!-- Current Payment Methods -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Mes cartes bancaires</h3>

            <div v-if="cards?.length > 0" class="space-y-3">
                <div
                    v-for="card in cards"
                    :key="card.id"
                    class="relative p-4 rounded-xl border-2 transition"
                    :class="card.is_default ? 'border-primary-500 bg-primary-50' : 'border-gray-200 bg-gray-50'"
                >
                    <!-- Default Badge -->
                    <div v-if="card.is_default" class="absolute top-2 right-2">
                        <span class="px-2 py-1 bg-primary-600 text-white text-xs rounded-full">Par defaut</span>
                    </div>

                    <div class="flex items-center">
                        <div class="w-14 h-10 bg-white rounded-lg flex items-center justify-center mr-4 shadow-sm">
                            <img
                                v-if="card.brand === 'visa'"
                                src="/images/cards/visa.svg"
                                alt="Visa"
                                class="h-6"
                            />
                            <img
                                v-else-if="card.brand === 'mastercard'"
                                src="/images/cards/mastercard.svg"
                                alt="Mastercard"
                                class="h-6"
                            />
                            <CreditCardIcon v-else class="w-6 h-6 text-gray-400" />
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900">•••• {{ card.last4 }}</p>
                            <p class="text-sm text-gray-500">Expire {{ card.exp_month }}/{{ card.exp_year }}</p>
                        </div>
                    </div>

                    <div class="flex space-x-2 mt-4">
                        <button
                            v-if="!card.is_default"
                            @click="setDefault(card)"
                            class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium"
                        >
                            Definir par defaut
                        </button>
                        <button
                            @click="deleteCard(card)"
                            class="px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg text-sm font-medium"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-8">
                <CreditCardIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                <p class="text-gray-500">Aucune carte enregistree</p>
            </div>

            <!-- Add Card Button -->
            <button
                @click="showAddCard = true"
                class="w-full mt-4 flex items-center justify-center px-4 py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-600 hover:border-primary-500 hover:text-primary-600 transition"
            >
                <PlusIcon class="w-5 h-5 mr-2" />
                Ajouter une carte
            </button>
        </div>

        <!-- SEPA Direct Debit -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Prelevement SEPA</h3>

            <div v-if="sepaMandate" class="p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="flex items-center mb-3">
                    <CheckCircleIcon class="w-6 h-6 text-green-600 mr-2" />
                    <span class="font-semibold text-green-800">Mandat actif</span>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">IBAN</span>
                        <span class="font-mono text-gray-900">{{ maskIban(sepaMandate.iban) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Reference</span>
                        <span class="text-gray-900">{{ sepaMandate.reference }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Date signature</span>
                        <span class="text-gray-900">{{ formatDate(sepaMandate.signed_at) }}</span>
                    </div>
                </div>

                <button
                    @click="revokeSepaMandate"
                    class="w-full mt-4 px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg text-sm font-medium"
                >
                    Revoquer le mandat
                </button>
            </div>

            <div v-else class="text-center py-6">
                <BuildingLibraryIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                <p class="text-gray-500 mb-4">Aucun mandat SEPA actif</p>
                <button
                    @click="showSepaSetup = true"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg font-medium"
                >
                    Configurer le prelevement
                </button>
            </div>
        </div>

        <!-- Auto-Pay Settings -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Paiement automatique</h3>

            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                <div>
                    <p class="font-medium text-gray-900">Activer le paiement auto</p>
                    <p class="text-sm text-gray-500">Payer automatiquement vos factures</p>
                </div>
                <button
                    @click="toggleAutoPay"
                    class="relative w-12 h-6 rounded-full transition-colors"
                    :class="autoPay ? 'bg-primary-600' : 'bg-gray-300'"
                >
                    <span
                        class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                        :class="autoPay ? 'translate-x-6' : ''"
                    ></span>
                </button>
            </div>

            <div v-if="autoPay" class="mt-4 p-4 bg-blue-50 rounded-xl">
                <div class="flex items-start">
                    <InformationCircleIcon class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" />
                    <p class="text-sm text-blue-700">
                        Vos factures seront automatiquement prelevees le 1er du mois sur votre moyen de paiement par defaut.
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment History Link -->
        <Link
            :href="route('mobile.payments')"
            class="block bg-white rounded-2xl shadow-sm p-5 mb-4"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <ClockIcon class="w-6 h-6 text-gray-400 mr-3" />
                    <span class="font-medium text-gray-900">Historique des paiements</span>
                </div>
                <ChevronRightIcon class="w-5 h-5 text-gray-400" />
            </div>
        </Link>

        <!-- Add Card Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showAddCard" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" @click="showAddCard = false"></div>
                <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-xl">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Ajouter une carte</h3>

                        <form @submit.prevent="addCard" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Numero de carte</label>
                                <input
                                    v-model="cardForm.number"
                                    type="text"
                                    placeholder="1234 5678 9012 3456"
                                    maxlength="19"
                                    @input="formatCardNumber"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiration</label>
                                    <input
                                        v-model="cardForm.expiry"
                                        type="text"
                                        placeholder="MM/AA"
                                        maxlength="5"
                                        @input="formatExpiry"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                                    <input
                                        v-model="cardForm.cvv"
                                        type="text"
                                        placeholder="123"
                                        maxlength="4"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Titulaire</label>
                                <input
                                    v-model="cardForm.holder"
                                    type="text"
                                    placeholder="NOM PRENOM"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 uppercase"
                                />
                            </div>

                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="cardForm.setDefault"
                                    id="setDefault"
                                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <label for="setDefault" class="ml-2 text-sm text-gray-700">
                                    Definir comme moyen de paiement par defaut
                                </label>
                            </div>

                            <div class="flex space-x-3 pt-2">
                                <button
                                    type="button"
                                    @click="showAddCard = false"
                                    class="flex-1 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="addingCard"
                                    class="flex-1 py-3 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                                >
                                    {{ addingCard ? 'Ajout...' : 'Ajouter' }}
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center text-sm text-gray-500">
                                <LockClosedIcon class="w-4 h-4 mr-2" />
                                Paiement securise par Stripe
                            </div>
                        </div>
                    </div>
                    <div class="h-8 bg-white"></div>
                </div>
            </div>
        </Transition>

        <!-- SEPA Setup Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showSepaSetup" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" @click="showSepaSetup = false"></div>
                <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-xl max-h-[90vh] overflow-y-auto">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Prelevement SEPA</h3>

                        <form @submit.prevent="setupSepa" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Titulaire du compte</label>
                                <input
                                    v-model="sepaForm.holder"
                                    type="text"
                                    placeholder="Nom du titulaire"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">IBAN</label>
                                <input
                                    v-model="sepaForm.iban"
                                    type="text"
                                    placeholder="FR76 1234 5678 9012 3456 7890 123"
                                    @input="formatIban"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 font-mono"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">BIC (optionnel)</label>
                                <input
                                    v-model="sepaForm.bic"
                                    type="text"
                                    placeholder="BNPAFRPP"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 font-mono"
                                />
                            </div>

                            <!-- Mandate Text -->
                            <div class="p-4 bg-gray-50 rounded-xl text-sm text-gray-600">
                                <p class="font-medium text-gray-900 mb-2">Mandat de prelevement SEPA</p>
                                <p>
                                    En signant ce formulaire de mandat, vous autorisez Boxibox a envoyer des instructions
                                    a votre banque pour debiter votre compte, et votre banque a debiter votre compte
                                    conformement aux instructions de Boxibox.
                                </p>
                            </div>

                            <div class="flex items-start">
                                <input
                                    type="checkbox"
                                    v-model="sepaForm.accept"
                                    id="acceptSepa"
                                    class="w-4 h-4 mt-1 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <label for="acceptSepa" class="ml-2 text-sm text-gray-700">
                                    J'accepte les conditions du mandat de prelevement SEPA
                                </label>
                            </div>

                            <div class="flex space-x-3 pt-2">
                                <button
                                    type="button"
                                    @click="showSepaSetup = false"
                                    class="flex-1 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="settingUpSepa || !sepaForm.accept"
                                    class="flex-1 py-3 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                                >
                                    {{ settingUpSepa ? 'Validation...' : 'Valider le mandat' }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="h-8 bg-white"></div>
                </div>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CreditCardIcon,
    PlusIcon,
    TrashIcon,
    CheckCircleIcon,
    BuildingLibraryIcon,
    InformationCircleIcon,
    ClockIcon,
    ChevronRightIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    cards: Array,
    sepaMandate: Object,
    autoPay: Boolean,
})

const showAddCard = ref(false)
const showSepaSetup = ref(false)
const addingCard = ref(false)
const settingUpSepa = ref(false)
const autoPay = ref(props.autoPay ?? false)

const cardForm = ref({
    number: '',
    expiry: '',
    cvv: '',
    holder: '',
    setDefault: true,
})

const sepaForm = ref({
    holder: '',
    iban: '',
    bic: '',
    accept: false,
})

const formatCardNumber = (e) => {
    let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '')
    value = value.match(/.{1,4}/g)?.join(' ') || value
    cardForm.value.number = value
}

const formatExpiry = (e) => {
    let value = e.target.value.replace(/\D/g, '')
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2)
    }
    cardForm.value.expiry = value
}

const formatIban = (e) => {
    let value = e.target.value.replace(/\s/g, '').toUpperCase()
    value = value.match(/.{1,4}/g)?.join(' ') || value
    sepaForm.value.iban = value
}

const maskIban = (iban) => {
    if (!iban) return ''
    const clean = iban.replace(/\s/g, '')
    return clean.slice(0, 4) + ' **** **** **** ' + clean.slice(-4)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
}

const addCard = () => {
    addingCard.value = true
    router.post(route('mobile.payment-methods.add-card'), cardForm.value, {
        onFinish: () => {
            addingCard.value = false
            showAddCard.value = false
            cardForm.value = { number: '', expiry: '', cvv: '', holder: '', setDefault: true }
        },
    })
}

const setDefault = (card) => {
    router.put(route('mobile.payment-methods.set-default', card.id))
}

const deleteCard = (card) => {
    if (confirm('Supprimer cette carte ?')) {
        router.delete(route('mobile.payment-methods.delete-card', card.id))
    }
}

const setupSepa = () => {
    if (!sepaForm.value.accept) return

    settingUpSepa.value = true
    router.post(route('mobile.payment-methods.setup-sepa'), sepaForm.value, {
        onFinish: () => {
            settingUpSepa.value = false
            showSepaSetup.value = false
            sepaForm.value = { holder: '', iban: '', bic: '', accept: false }
        },
    })
}

const revokeSepaMandate = () => {
    if (confirm('Revoquer le mandat SEPA ? Vous ne pourrez plus etre preleve automatiquement.')) {
        router.delete(route('mobile.payment-methods.revoke-sepa'))
    }
}

const toggleAutoPay = () => {
    autoPay.value = !autoPay.value
    router.put(route('mobile.payment-methods.toggle-autopay'), { enabled: autoPay.value })
}
</script>
