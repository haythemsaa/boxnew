<template>
    <MobileLayout :title="'Aide & FAQ'" :showBack="true">
        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative">
                <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher une question..."
                    class="w-full pl-12 pr-4 py-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                />
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute right-4 top-1/2 -translate-y-1/2 p-1"
                >
                    <XMarkIcon class="w-5 h-5 text-gray-400" />
                </button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <Link
                :href="route('mobile.chat')"
                class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl p-5 text-white shadow-lg shadow-primary-500/30"
            >
                <ChatBubbleLeftRightIcon class="w-8 h-8 mb-3" />
                <h3 class="font-bold">Chat en direct</h3>
                <p class="text-primary-100 text-sm mt-1">Réponse immédiate</p>
            </Link>
            <a
                href="tel:+33100000000"
                class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-green-500/30"
            >
                <PhoneIcon class="w-8 h-8 mb-3" />
                <h3 class="font-bold">Nous appeler</h3>
                <p class="text-green-100 text-sm mt-1">01 00 00 00 00</p>
            </a>
        </div>

        <!-- FAQ Categories -->
        <div class="mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Catégories</h2>
            <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    @click="selectedCategory = category.id"
                    class="flex-shrink-0 px-4 py-2 rounded-full font-medium text-sm transition-all whitespace-nowrap"
                    :class="selectedCategory === category.id
                        ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
                >
                    {{ category.name }}
                </button>
            </div>
        </div>

        <!-- FAQ List -->
        <div class="space-y-3 mb-6">
            <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-for="faq in filteredFaqs"
                    :key="faq.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden"
                >
                    <button
                        @click="toggleFaq(faq.id)"
                        class="w-full flex items-center justify-between p-5 text-left"
                    >
                        <div class="flex items-center flex-1 mr-4">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center mr-4 flex-shrink-0"
                                :class="getCategoryColor(faq.category)"
                            >
                                <component :is="getCategoryIcon(faq.category)" class="w-5 h-5 text-white" />
                            </div>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ faq.question }}</span>
                        </div>
                        <ChevronDownIcon
                            class="w-5 h-5 text-gray-400 transition-transform duration-300"
                            :class="{ 'rotate-180': openFaqs.includes(faq.id) }"
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
                        <div v-if="openFaqs.includes(faq.id)" class="overflow-hidden">
                            <div class="px-5 pb-5 pt-0">
                                <div class="pl-14 text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                                    {{ faq.answer }}
                                </div>
                                <div class="pl-14 mt-4 flex items-center gap-3">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Cette réponse vous a-t-elle aidé ?</span>
                                    <button
                                        @click.stop="rateFaq(faq.id, true)"
                                        class="p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/30 transition-colors"
                                        :class="faqRatings[faq.id] === true ? 'bg-green-100 dark:bg-green-900/50' : ''"
                                    >
                                        <HandThumbUpIcon class="w-5 h-5 text-green-600" />
                                    </button>
                                    <button
                                        @click.stop="rateFaq(faq.id, false)"
                                        class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                        :class="faqRatings[faq.id] === false ? 'bg-red-100 dark:bg-red-900/50' : ''"
                                    >
                                        <HandThumbDownIcon class="w-5 h-5 text-red-600" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </TransitionGroup>

            <!-- No results -->
            <div v-if="filteredFaqs.length === 0" class="text-center py-12">
                <QuestionMarkCircleIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aucun résultat</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Essayez d'autres mots-clés ou contactez-nous</p>
            </div>
        </div>

        <!-- Contact Options -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 mb-6">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">Autres moyens de nous contacter</h3>
            <div class="space-y-3">
                <a
                    href="mailto:support@boxibox.com"
                    class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center mr-4">
                        <EnvelopeIcon class="w-5 h-5 text-white" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white">Email</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">support@boxibox.com</p>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>
                <a
                    href="https://wa.me/33100000000"
                    target="_blank"
                    class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white">WhatsApp</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Messagerie instantanée</p>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>
            </div>
        </div>

        <!-- App Info -->
        <div class="text-center pb-6">
            <p class="text-xs text-gray-400 dark:text-gray-500">Boxibox Mobile v1.0.0</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">© 2024 Boxibox. Tous droits réservés.</p>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    MagnifyingGlassIcon,
    XMarkIcon,
    ChatBubbleLeftRightIcon,
    PhoneIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    QuestionMarkCircleIcon,
    EnvelopeIcon,
    HandThumbUpIcon,
    HandThumbDownIcon,
    CubeIcon,
    CreditCardIcon,
    KeyIcon,
    ShieldCheckIcon,
    TruckIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline'

const searchQuery = ref('')
const selectedCategory = ref('all')
const openFaqs = ref([])
const faqRatings = reactive({})

const categories = [
    { id: 'all', name: 'Tout' },
    { id: 'storage', name: 'Stockage' },
    { id: 'payment', name: 'Paiement' },
    { id: 'access', name: 'Accès' },
    { id: 'security', name: 'Sécurité' },
    { id: 'moving', name: 'Déménagement' },
    { id: 'contract', name: 'Contrat' },
]

const faqs = [
    {
        id: 1,
        category: 'storage',
        question: 'Quelles sont les dimensions des box disponibles ?',
        answer: 'Nous proposons des box de différentes tailles : 1m² à 50m². Les dimensions les plus courantes sont 3m², 5m², 10m² et 20m². Chaque box a une hauteur de 2,50m minimum.'
    },
    {
        id: 2,
        category: 'storage',
        question: 'Puis-je stocker des objets fragiles ?',
        answer: 'Oui, nos box sont parfaitement adaptés au stockage d\'objets fragiles. Nous vous recommandons d\'utiliser des matériaux de protection (papier bulle, couvertures) et des cartons adaptés. Nos box sont secs et ventilés.'
    },
    {
        id: 3,
        category: 'payment',
        question: 'Quels modes de paiement acceptez-vous ?',
        answer: 'Nous acceptons les cartes bancaires (Visa, Mastercard), les virements SEPA, les prélèvements automatiques et les chèques. Le paiement par espèces est possible sur site.'
    },
    {
        id: 4,
        category: 'payment',
        question: 'Puis-je payer mensuellement ?',
        answer: 'Oui, le paiement mensuel est notre mode de facturation standard. Vous pouvez également opter pour un paiement trimestriel ou annuel avec des réductions.'
    },
    {
        id: 5,
        category: 'access',
        question: 'Quels sont les horaires d\'accès ?',
        answer: 'L\'accès à votre box est disponible 7j/7 de 6h à 22h. Un accès 24h/24 peut être souscrit en option moyennant un supplément mensuel.'
    },
    {
        id: 6,
        category: 'access',
        question: 'Comment accéder à mon box ?',
        answer: 'Vous accédez au site avec votre code personnel ou votre badge. Chaque box dispose de son propre cadenas dont vous êtes le seul détenteur des clés. L\'application mobile vous permet également de générer un QR code d\'accès.'
    },
    {
        id: 7,
        category: 'security',
        question: 'Mon box est-il sécurisé ?',
        answer: 'Absolument. Nos sites disposent d\'une vidéosurveillance 24h/24, d\'alarmes individuelles, de détection de mouvement et d\'un gardiennage. Chaque accès est enregistré et horodaté.'
    },
    {
        id: 8,
        category: 'security',
        question: 'Mes biens sont-ils assurés ?',
        answer: 'Une assurance de base est incluse dans votre contrat. Vous pouvez souscrire à une assurance complémentaire pour une protection renforcée selon la valeur de vos biens.'
    },
    {
        id: 9,
        category: 'moving',
        question: 'Proposez-vous un service de déménagement ?',
        answer: 'Oui, nous proposons des services de déménagement en partenariat avec des professionnels. Contactez-nous pour obtenir un devis personnalisé.'
    },
    {
        id: 10,
        category: 'contract',
        question: 'Quelle est la durée minimum de location ?',
        answer: 'La durée minimum est d\'un mois. Le contrat est sans engagement et résiliable à tout moment avec un préavis de 15 jours avant la fin du mois en cours.'
    },
    {
        id: 11,
        category: 'contract',
        question: 'Comment résilier mon contrat ?',
        answer: 'Vous pouvez résilier depuis l\'application mobile, par email ou en vous rendant sur site. Le préavis est de 15 jours. Votre box doit être vidé et propre à la date de fin de contrat.'
    },
]

const filteredFaqs = computed(() => {
    let result = faqs

    // Filter by category
    if (selectedCategory.value !== 'all') {
        result = result.filter(faq => faq.category === selectedCategory.value)
    }

    // Filter by search query
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(faq =>
            faq.question.toLowerCase().includes(query) ||
            faq.answer.toLowerCase().includes(query)
        )
    }

    return result
})

const toggleFaq = (id) => {
    const index = openFaqs.value.indexOf(id)
    if (index > -1) {
        openFaqs.value.splice(index, 1)
    } else {
        openFaqs.value.push(id)
    }
}

const rateFaq = (id, helpful) => {
    faqRatings[id] = helpful
    // Would send to API in production
}

const getCategoryIcon = (category) => {
    const icons = {
        storage: CubeIcon,
        payment: CreditCardIcon,
        access: KeyIcon,
        security: ShieldCheckIcon,
        moving: TruckIcon,
        contract: DocumentTextIcon,
    }
    return icons[category] || QuestionMarkCircleIcon
}

const getCategoryColor = (category) => {
    const colors = {
        storage: 'bg-gradient-to-br from-primary-400 to-primary-600',
        payment: 'bg-gradient-to-br from-green-400 to-emerald-600',
        access: 'bg-gradient-to-br from-purple-400 to-violet-600',
        security: 'bg-gradient-to-br from-blue-400 to-indigo-600',
        moving: 'bg-gradient-to-br from-orange-400 to-red-500',
        contract: 'bg-gradient-to-br from-gray-400 to-gray-600',
    }
    return colors[category] || 'bg-gradient-to-br from-gray-400 to-gray-600'
}
</script>
