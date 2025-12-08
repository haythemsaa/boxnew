<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="py-16 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-6">
                        Calculez vos
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            économies
                        </span>
                    </h1>
                    <p class="text-xl text-gray-600">
                        Découvrez combien vous pourriez économiser en passant à Boxibox.
                    </p>
                </div>
            </div>
        </section>

        <!-- Calculator Section -->
        <section class="py-16 bg-white -mt-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Calculator Form -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Vos informations</h2>

                        <div class="space-y-6">
                            <!-- Number of Sites -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre de sites
                                </label>
                                <input
                                    v-model.number="form.sites"
                                    type="range"
                                    min="1"
                                    max="20"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                                />
                                <div class="flex justify-between text-sm text-gray-500 mt-1">
                                    <span>1</span>
                                    <span class="font-semibold text-indigo-600">{{ form.sites }} site{{ form.sites > 1 ? 's' : '' }}</span>
                                    <span>20+</span>
                                </div>
                            </div>

                            <!-- Number of Boxes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre total de boxes
                                </label>
                                <input
                                    v-model.number="form.boxes"
                                    type="range"
                                    min="10"
                                    max="1000"
                                    step="10"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                                />
                                <div class="flex justify-between text-sm text-gray-500 mt-1">
                                    <span>10</span>
                                    <span class="font-semibold text-indigo-600">{{ form.boxes }} boxes</span>
                                    <span>1000+</span>
                                </div>
                            </div>

                            <!-- Average Price per Box -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Loyer moyen par box (€/mois)
                                </label>
                                <input
                                    v-model.number="form.avgPrice"
                                    type="number"
                                    min="20"
                                    max="500"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="80"
                                />
                            </div>

                            <!-- Current Occupancy -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Taux d'occupation actuel
                                </label>
                                <input
                                    v-model.number="form.occupancy"
                                    type="range"
                                    min="30"
                                    max="100"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                                />
                                <div class="flex justify-between text-sm text-gray-500 mt-1">
                                    <span>30%</span>
                                    <span class="font-semibold text-indigo-600">{{ form.occupancy }}%</span>
                                    <span>100%</span>
                                </div>
                            </div>

                            <!-- Hours per week on admin -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Heures admin par semaine
                                </label>
                                <input
                                    v-model.number="form.adminHours"
                                    type="range"
                                    min="5"
                                    max="60"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                                />
                                <div class="flex justify-between text-sm text-gray-500 mt-1">
                                    <span>5h</span>
                                    <span class="font-semibold text-indigo-600">{{ form.adminHours }}h/sem</span>
                                    <span>60h</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div>
                        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-8 text-white mb-8">
                            <h3 class="text-lg font-medium text-indigo-100 mb-4">Économies annuelles estimées</h3>
                            <p class="text-5xl font-bold mb-2">{{ formatCurrency(totalSavings) }}</p>
                            <p class="text-indigo-200">Par an avec Boxibox</p>
                        </div>

                        <div class="space-y-4">
                            <!-- Time Savings -->
                            <div class="bg-white rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900">Temps économisé</span>
                                    </div>
                                    <span class="text-xl font-bold text-gray-900">{{ timeSaved }}h/sem</span>
                                </div>
                                <p class="text-sm text-gray-500">Automatisation des tâches administratives</p>
                            </div>

                            <!-- Occupancy Increase -->
                            <div class="bg-white rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900">Hausse d'occupation</span>
                                    </div>
                                    <span class="text-xl font-bold text-green-600">+{{ occupancyIncrease }}%</span>
                                </div>
                                <p class="text-sm text-gray-500">Grâce au booking en ligne et au CRM</p>
                            </div>

                            <!-- Revenue Increase -->
                            <div class="bg-white rounded-xl p-6 border border-gray-200">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-gray-900">Revenus additionnels</span>
                                    </div>
                                    <span class="text-xl font-bold text-purple-600">{{ formatCurrency(additionalRevenue) }}/an</span>
                                </div>
                                <p class="text-sm text-gray-500">Dynamic pricing et optimisation</p>
                            </div>

                            <!-- Recommended Plan -->
                            <div class="bg-indigo-50 rounded-xl p-6 border border-indigo-100 mt-6">
                                <div class="flex items-center gap-3 mb-3">
                                    <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-semibold text-gray-900">Plan recommandé: {{ recommendedPlan }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mb-4">Basé sur {{ form.sites }} site{{ form.sites > 1 ? 's' : '' }} et {{ form.boxes }} boxes</p>
                                <Link href="/demo" class="btn-primary w-full">
                                    Essayer gratuitement
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How We Calculate Section -->
        <section class="py-24 bg-gray-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Comment calculons-nous vos économies ?</h2>
                    <p class="text-xl text-gray-600">Nos estimations sont basées sur les résultats réels de nos clients.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">1</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Automatisation</h3>
                        <p class="text-gray-600 text-sm">En moyenne, nos clients économisent 40% de leur temps administratif grâce à l'automatisation.</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">2</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Occupation</h3>
                        <p class="text-gray-600 text-sm">Le booking en ligne et le CRM augmentent le taux d'occupation de 5-15% en moyenne.</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-indigo-600">3</span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Dynamic Pricing</h3>
                        <p class="text-gray-600 text-sm">L'optimisation des prix permet d'augmenter le revenu par box de 8-12%.</p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const form = ref({
    sites: 1,
    boxes: 100,
    avgPrice: 80,
    occupancy: 75,
    adminHours: 20,
})

const hourlyRate = 25 // Cost per hour of admin work

const timeSaved = computed(() => {
    return Math.round(form.value.adminHours * 0.4) // 40% time savings
})

const occupancyIncrease = computed(() => {
    // 5-15% increase based on current occupancy
    const currentOccupancy = form.value.occupancy
    if (currentOccupancy >= 90) return 3
    if (currentOccupancy >= 80) return 5
    if (currentOccupancy >= 70) return 8
    return 12
})

const additionalRevenue = computed(() => {
    const monthlyRevenue = form.value.boxes * form.value.avgPrice * (form.value.occupancy / 100)
    const newOccupancy = Math.min(100, form.value.occupancy + occupancyIncrease.value)
    const newMonthlyRevenue = form.value.boxes * form.value.avgPrice * (newOccupancy / 100)
    const dynamicPricingBonus = newMonthlyRevenue * 0.08 // 8% increase from dynamic pricing
    return Math.round((newMonthlyRevenue - monthlyRevenue + dynamicPricingBonus) * 12)
})

const timeSavingsValue = computed(() => {
    return timeSaved.value * hourlyRate * 52 // Annual savings
})

const totalSavings = computed(() => {
    return additionalRevenue.value + timeSavingsValue.value
})

const recommendedPlan = computed(() => {
    if (form.value.sites > 3 || form.value.boxes > 200) return 'Pro'
    if (form.value.sites > 1 || form.value.boxes > 50) return 'Growth'
    return 'Starter'
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value)
}
</script>
