<template>
    <MobileLayout title="FAQ" :show-back="true">
        <!-- Search -->
        <div class="relative mb-4">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher une question..."
                class="w-full pl-10 pr-4 py-3 bg-white border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500"
            />
        </div>

        <!-- Categories -->
        <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
            <button
                v-for="cat in categories"
                :key="cat.id"
                @click="activeCategory = cat.id"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    activeCategory === cat.id ? 'bg-primary-600 text-white' : 'bg-white text-gray-700'
                ]"
            >
                {{ cat.name }}
            </button>
        </div>

        <!-- FAQ List -->
        <div class="space-y-3">
            <div
                v-for="(faq, index) in filteredFaqs"
                :key="index"
                class="bg-white rounded-xl shadow-sm overflow-hidden"
            >
                <button
                    @click="toggleFaq(index)"
                    class="w-full p-4 text-left flex items-center justify-between"
                >
                    <span class="font-medium text-gray-900 pr-4">{{ faq.question }}</span>
                    <ChevronDownIcon
                        class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform"
                        :class="{ 'rotate-180': openFaqs.includes(index) }"
                    />
                </button>
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="max-h-0 opacity-0"
                    enter-to-class="max-h-96 opacity-100"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="max-h-96 opacity-100"
                    leave-to-class="max-h-0 opacity-0"
                >
                    <div v-if="openFaqs.includes(index)" class="overflow-hidden">
                        <div class="px-4 pb-4 text-gray-600 border-t border-gray-100 pt-3">
                            {{ faq.answer }}
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- No Results -->
        <div v-if="filteredFaqs.length === 0" class="text-center py-12">
            <QuestionMarkCircleIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun resultat</h3>
            <p class="text-gray-500 mb-4">Aucune question ne correspond a votre recherche</p>
            <Link
                :href="route('mobile.support')"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg"
            >
                Contacter le support
            </Link>
        </div>

        <!-- Still Need Help -->
        <div class="bg-primary-50 rounded-2xl p-5 mt-6">
            <h3 class="text-lg font-semibold text-primary-900 mb-2">Vous n'avez pas trouve de reponse ?</h3>
            <p class="text-primary-700 text-sm mb-4">
                Notre equipe de support est disponible pour vous aider.
            </p>
            <Link
                :href="route('mobile.support')"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg font-medium"
            >
                <ChatBubbleLeftRightIcon class="w-5 h-5 mr-2" />
                Contacter le support
            </Link>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    MagnifyingGlassIcon,
    ChevronDownIcon,
    QuestionMarkCircleIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'

const searchQuery = ref('')
const activeCategory = ref('all')
const openFaqs = ref([])

const categories = [
    { id: 'all', name: 'Toutes' },
    { id: 'reservation', name: 'Reservation' },
    { id: 'contract', name: 'Contrat' },
    { id: 'payment', name: 'Paiement' },
    { id: 'access', name: 'Acces' },
    { id: 'insurance', name: 'Assurance' },
]

const faqs = [
    // Reservation
    {
        category: 'reservation',
        question: 'Comment reserver un box ?',
        answer: 'Vous pouvez reserver un box directement depuis l\'application en allant dans "Reserver" depuis le menu principal. Selectionnez votre site, choisissez la taille du box adaptee a vos besoins, puis completez la reservation en ligne.',
    },
    {
        category: 'reservation',
        question: 'Combien de temps a l\'avance puis-je reserver ?',
        answer: 'Vous pouvez reserver un box jusqu\'a 3 mois a l\'avance. La reservation est confirmee des le paiement du premier mois et du depot de garantie.',
    },
    {
        category: 'reservation',
        question: 'Comment choisir la bonne taille de box ?',
        answer: 'Utilisez notre calculateur de taille integre dans l\'application. Indiquez les objets que vous souhaitez stocker et nous vous recommanderons la taille ideale.',
    },

    // Contract
    {
        category: 'contract',
        question: 'Quelle est la duree minimale de location ?',
        answer: 'La duree minimale de location est de 1 mois. Vous pouvez ensuite renouveler votre contrat mensuellement ou pour une duree plus longue.',
    },
    {
        category: 'contract',
        question: 'Comment resilier mon contrat ?',
        answer: 'Vous pouvez resilier votre contrat depuis l\'application dans la section "Mes Contrats". Le preavis est d\'un mois. Votre depot de garantie vous sera restitue apres l\'etat des lieux de sortie.',
    },
    {
        category: 'contract',
        question: 'Puis-je changer de box en cours de contrat ?',
        answer: 'Oui, vous pouvez demander un changement de box (plus grand ou plus petit) en contactant notre service client. Le changement sera effectue sous reserve de disponibilite.',
    },

    // Payment
    {
        category: 'payment',
        question: 'Quels sont les moyens de paiement acceptes ?',
        answer: 'Nous acceptons les cartes bancaires (Visa, Mastercard, CB), les prelevements SEPA et les virements bancaires. Le paiement par cheque n\'est pas accepte.',
    },
    {
        category: 'payment',
        question: 'Quand sont prelevees les mensualites ?',
        answer: 'Les mensualites sont prelevees le 1er de chaque mois pour le mois en cours. Vous recevez une facture par email quelques jours avant.',
    },
    {
        category: 'payment',
        question: 'Comment obtenir une facture ?',
        answer: 'Toutes vos factures sont disponibles dans la section "Mes Factures" de l\'application. Vous pouvez les telecharger au format PDF.',
    },

    // Access
    {
        category: 'access',
        question: 'Quels sont les horaires d\'acces ?',
        answer: 'Les horaires d\'acces varient selon les sites. En general, l\'acces est possible de 6h a 22h en semaine et de 7h a 21h le week-end. Certains sites proposent un acces 24h/24.',
    },
    {
        category: 'access',
        question: 'Comment acceder a mon box ?',
        answer: 'Un code d\'acces personnel vous est communique lors de la signature de votre contrat. Ce code vous permet d\'ouvrir le portail et d\'acceder a votre box. Vous pouvez aussi utiliser le QR code dans l\'application.',
    },
    {
        category: 'access',
        question: 'Puis-je donner acces a quelqu\'un d\'autre ?',
        answer: 'Oui, vous pouvez autoriser une tierce personne a acceder a votre box en lui communiquant votre code d\'acces ou en ajoutant une personne autorisee dans votre contrat.',
    },

    // Insurance
    {
        category: 'insurance',
        question: 'Mes biens sont-ils assures ?',
        answer: 'Une assurance de base est incluse dans votre contrat. Nous vous recommandons de verifier la couverture et de souscrire une assurance complementaire si necessaire.',
    },
    {
        category: 'insurance',
        question: 'Quels biens ne sont pas assures ?',
        answer: 'Les objets de valeur (bijoux, especes, oeuvres d\'art), les produits perissables, inflammables ou dangereux ne sont pas couverts. Consultez les conditions generales pour plus de details.',
    },
]

const filteredFaqs = computed(() => {
    let result = faqs

    if (activeCategory.value !== 'all') {
        result = result.filter(f => f.category === activeCategory.value)
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(f =>
            f.question.toLowerCase().includes(query) ||
            f.answer.toLowerCase().includes(query)
        )
    }

    return result
})

const toggleFaq = (index) => {
    const i = openFaqs.value.indexOf(index)
    if (i === -1) {
        openFaqs.value.push(index)
    } else {
        openFaqs.value.splice(i, 1)
    }
}
</script>
