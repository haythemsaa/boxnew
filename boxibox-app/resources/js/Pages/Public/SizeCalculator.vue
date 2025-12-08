<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="py-16 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-8">
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-6">
                        Quelle taille de
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            box
                        </span>
                        choisir ?
                    </h1>
                    <p class="text-xl text-gray-600">
                        Utilisez notre calculateur pour estimer la taille de box dont vous avez besoin.
                    </p>
                </div>
            </div>
        </section>

        <!-- Calculator Section -->
        <section class="py-8 bg-white -mt-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                    <!-- Calculator Widget -->
                    <div>
                        <SizeCalculatorWidget
                            :primary-color="'#6366f1'"
                            :available-boxes="sampleBoxes"
                            booking-url="/demo"
                            @recommend="handleRecommendation"
                        />
                    </div>

                    <!-- Info Panel -->
                    <div class="space-y-8">
                        <!-- Tips -->
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <LightBulbIcon class="h-6 w-6 text-indigo-600" />
                                Conseils pour bien choisir
                            </h3>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span class="text-gray-600">
                                        <strong class="text-gray-900">Prévoyez 20% de plus</strong> pour pouvoir circuler et accéder à vos affaires
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span class="text-gray-600">
                                        <strong class="text-gray-900">Utilisez des cartons empilables</strong> pour optimiser l'espace vertical
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span class="text-gray-600">
                                        <strong class="text-gray-900">Démontez les meubles</strong> si possible pour gagner de la place
                                    </span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <CheckCircleIcon class="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span class="text-gray-600">
                                        <strong class="text-gray-900">Placez les objets lourds</strong> en bas et les légers en haut
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <!-- Size Guide -->
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Guide des tailles</h3>
                            <div class="space-y-4">
                                <div v-for="size in sizeGuide" :key="size.name" class="flex items-center gap-4 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                                    <div class="text-3xl">{{ size.icon }}</div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-semibold text-gray-900">{{ size.name }}</span>
                                            <span class="text-indigo-600 font-medium">{{ size.volume }}</span>
                                        </div>
                                        <p class="text-sm text-gray-500">{{ size.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-8 text-white text-center">
                            <h3 class="text-2xl font-bold mb-3">Besoin d'aide ?</h3>
                            <p class="text-white/80 mb-6">
                                Nos conseillers sont disponibles pour vous aider à choisir la taille idéale.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                <a href="/contact" class="bg-white text-indigo-600 px-6 py-3 rounded-xl font-semibold hover:bg-gray-100 transition-colors">
                                    Nous contacter
                                </a>
                                <a href="/demo" class="bg-white/20 text-white px-6 py-3 rounded-xl font-semibold hover:bg-white/30 transition-colors">
                                    Demander une démo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Questions fréquentes</h2>

                <div class="space-y-4">
                    <details v-for="(faq, index) in faqs" :key="index" class="group bg-white rounded-xl shadow-sm">
                        <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900 hover:text-indigo-600">
                            {{ faq.question }}
                            <ChevronDownIcon class="h-5 w-5 text-gray-400 group-open:rotate-180 transition-transform" />
                        </summary>
                        <div class="px-6 pb-6 text-gray-600">
                            {{ faq.answer }}
                        </div>
                    </details>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { ref } from 'vue'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import SizeCalculatorWidget from '@/Components/Public/SizeCalculatorWidget.vue'
import {
    LightBulbIcon,
    CheckCircleIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline'

// Sample boxes for recommendations
const sampleBoxes = ref([
    { id: 1, name: 'Box Casier', volume: 1, current_price: 29 },
    { id: 2, name: 'Box S', volume: 3, current_price: 49 },
    { id: 3, name: 'Box M', volume: 6, current_price: 79 },
    { id: 4, name: 'Box L', volume: 10, current_price: 119 },
    { id: 5, name: 'Box XL', volume: 18, current_price: 179 },
    { id: 6, name: 'Box XXL', volume: 30, current_price: 249 },
])

// Size guide
const sizeGuide = [
    { name: 'Casier', volume: '1-2 m³', icon: '📦', description: '10-20 cartons, valises, affaires saisonnières' },
    { name: 'Petit', volume: '3-5 m³', icon: '🗄️', description: 'Studio, chambre d\'étudiant, petit déménagement' },
    { name: 'Moyen', volume: '6-10 m³', icon: '🏠', description: 'Appartement T2, mobilier d\'une pièce' },
    { name: 'Grand', volume: '10-18 m³', icon: '🏡', description: 'Appartement T3/T4, petit entrepôt' },
    { name: 'Très grand', volume: '18-30 m³', icon: '🏘️', description: 'Maison, mobilier complet, archives entreprise' },
    { name: 'XXL', volume: '30+ m³', icon: '🏢', description: 'Grande maison, stock professionnel' },
]

// FAQs
const faqs = [
    {
        question: 'Comment calculer la taille de box dont j\'ai besoin ?',
        answer: 'Utilisez notre calculateur ci-dessus en sélectionnant les pièces ou les meubles que vous souhaitez stocker. Le calculateur ajoutera automatiquement 20% d\'espace supplémentaire pour vous permettre de circuler et d\'accéder à vos affaires.',
    },
    {
        question: 'Puis-je changer de taille de box après la réservation ?',
        answer: 'Oui, vous pouvez passer à une taille supérieure ou inférieure selon vos besoins, sous réserve de disponibilité. Contactez notre équipe pour organiser le transfert.',
    },
    {
        question: 'Que puis-je stocker dans un box ?',
        answer: 'Vous pouvez stocker la plupart des objets personnels et professionnels : meubles, cartons, équipements sportifs, archives, stock commercial, etc. Les produits inflammables, périssables ou illicites sont interdits.',
    },
    {
        question: 'Comment optimiser l\'espace dans mon box ?',
        answer: 'Empilez les cartons du même format, démontez les meubles si possible, utilisez l\'espace vertical, et laissez une allée centrale pour accéder à vos affaires. Placez les objets que vous utilisez fréquemment à l\'entrée.',
    },
    {
        question: 'Le calcul inclut-il une marge de sécurité ?',
        answer: 'Oui, notre calculateur ajoute automatiquement 20% de volume supplémentaire pour vous permettre de circuler et d\'organiser vos affaires confortablement.',
    },
]

// Handle recommendation from calculator
const handleRecommendation = (recommendation) => {
    console.log('Recommendation:', recommendation)
}
</script>
