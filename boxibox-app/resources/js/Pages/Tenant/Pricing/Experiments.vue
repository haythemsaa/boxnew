<template>
    <Head :title="$t('pricing.experiments')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Tests A/B Pricing
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Optimisez vos prix avec des expériences scientifiques
                        </p>
                    </div>
                    <button @click="showCreateModal = true"
                        class="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouvelle expérience
                    </button>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Expériences actives</div>
                        <div class="mt-2 text-3xl font-semibold text-purple-600">{{ stats.active }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Expériences terminées</div>
                        <div class="mt-2 text-3xl font-semibold text-green-600">{{ stats.completed }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenus générés</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_revenue_impact) }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Taux de succès</div>
                        <div class="mt-2 text-3xl font-semibold text-blue-600">{{ stats.success_rate }}%</div>
                    </div>
                </div>

                <!-- Active Experiments -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            Expériences en cours
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="experiment in activeExperiments" :key="experiment.id" class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-white">{{ experiment.name }}</h4>
                                        <span :class="getStatusBadgeClass(experiment.status)"
                                            class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ getStatusLabel(experiment.status) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ experiment.description }}</p>

                                    <!-- Experiment Details -->
                                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div>
                                            <div class="text-xs text-gray-500">Boxes testées</div>
                                            <div class="font-semibold">{{ experiment.boxes_count || 0 }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Durée</div>
                                            <div class="font-semibold">{{ experiment.duration_days }} jours</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Variante A (Contrôle)</div>
                                            <div class="font-semibold">{{ experiment.control_price }}€</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-500">Variante B (Test)</div>
                                            <div class="font-semibold text-purple-600">{{ experiment.test_price }}€</div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mt-4">
                                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                                            <span>Progression</span>
                                            <span>{{ experiment.progress }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                                                :style="{ width: experiment.progress + '%' }"></div>
                                        </div>
                                    </div>

                                    <!-- Results Preview -->
                                    <div v-if="experiment.results" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <div class="grid grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <div class="text-gray-500">Conversions A</div>
                                                <div class="font-semibold">{{ experiment.results.control_conversions }}</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Conversions B</div>
                                                <div class="font-semibold">{{ experiment.results.test_conversions }}</div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500">Amélioration</div>
                                                <div class="font-semibold" :class="experiment.results.improvement > 0 ? 'text-green-600' : 'text-red-600'">
                                                    {{ experiment.results.improvement > 0 ? '+' : '' }}{{ experiment.results.improvement }}%
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex items-center gap-2">
                                            <span class="text-xs text-gray-500">Confiance statistique:</span>
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all"
                                                    :class="experiment.results.confidence >= 95 ? 'bg-green-500' : 'bg-yellow-500'"
                                                    :style="{ width: experiment.results.confidence + '%' }"></div>
                                            </div>
                                            <span class="text-xs font-medium">{{ experiment.results.confidence }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="ml-4 flex flex-col gap-2">
                                    <button @click="viewResults(experiment)"
                                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                        Résultats
                                    </button>
                                    <button v-if="experiment.status === 'running'" @click="pauseExperiment(experiment)"
                                        class="px-3 py-1.5 text-sm bg-yellow-600 text-white rounded hover:bg-yellow-700">
                                        Pause
                                    </button>
                                    <button v-if="experiment.status === 'paused'" @click="resumeExperiment(experiment)"
                                        class="px-3 py-1.5 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                        Reprendre
                                    </button>
                                    <button v-if="experiment.results?.confidence >= 95" @click="completeExperiment(experiment)"
                                        class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">
                                        Terminer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeExperiments.length === 0" class="p-8 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <p class="mt-2">Aucune expérience active. Créez votre premier test A/B !</p>
                        </div>
                    </div>
                </div>

                <!-- Completed Experiments -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Expériences terminées</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Période</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Variantes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Gagnant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Impact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="experiment in completedExperiments" :key="experiment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ experiment.name }}</div>
                                        <div class="text-sm text-gray-500">{{ experiment.boxes_count }} boxes</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatDate(experiment.started_at) }} - {{ formatDate(experiment.ended_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">A: {{ experiment.control_price }}€</span>
                                        <span class="mx-2 text-gray-400">vs</span>
                                        <span class="text-purple-600">B: {{ experiment.test_price }}€</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="experiment.winner === 'B' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'"
                                            class="px-2 py-1 text-xs font-medium rounded-full">
                                            Variante {{ experiment.winner }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="experiment.revenue_impact > 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
                                            {{ experiment.revenue_impact > 0 ? '+' : '' }}{{ formatCurrency(experiment.revenue_impact) }}/mois
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button @click="viewResults(experiment)" class="text-blue-600 hover:text-blue-800 text-sm">
                                            Détails
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="completedExperiments.length === 0" class="p-8 text-center text-gray-500">
                        Aucune expérience terminée
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Experiment Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 z-10">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Créer une expérience A/B</h3>

                        <form @submit.prevent="createExperiment">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom de l'expérience</label>
                                    <input v-model="newExperiment.name" type="text" required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea v-model="newExperiment.description" rows="2"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site</label>
                                        <select v-model="newExperiment.site_id" required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <option value="">Sélectionner un site</option>
                                            <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durée (jours)</label>
                                        <input v-model.number="newExperiment.duration_days" type="number" min="7" max="90" required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix contrôle (€)</label>
                                        <input v-model.number="newExperiment.control_price" type="number" step="0.01" required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prix test (€)</label>
                                        <input v-model.number="newExperiment.test_price" type="number" step="0.01" required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taille des boxes à tester</label>
                                    <select v-model="newExperiment.box_size_category"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">Toutes les tailles</option>
                                        <option value="small">Petit (< 5m²)</option>
                                        <option value="medium">Moyen (5-15m²)</option>
                                        <option value="large">Grand (> 15m²)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Répartition du trafic</label>
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-500">Contrôle: {{ 100 - newExperiment.traffic_split }}%</span>
                                        <input v-model.number="newExperiment.traffic_split" type="range" min="10" max="90" step="5"
                                            class="flex-1">
                                        <span class="text-sm text-purple-600">Test: {{ newExperiment.traffic_split }}%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" @click="showCreateModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Annuler
                                </button>
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
                                    {{ isSubmitting ? 'Création...' : 'Créer l\'expérience' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Results Modal -->
        <Teleport to="body">
            <div v-if="showResultsModal && selectedExperiment" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showResultsModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full p-6 z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ selectedExperiment.name }}</h3>
                                <p class="text-sm text-gray-500">{{ selectedExperiment.description }}</p>
                            </div>
                            <button @click="showResultsModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div v-if="selectedExperiment.results" class="space-y-6">
                            <!-- Comparison Chart -->
                            <div class="grid grid-cols-2 gap-6">
                                <div class="p-6 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center">
                                    <div class="text-sm text-gray-500 mb-2">Variante A (Contrôle)</div>
                                    <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ selectedExperiment.control_price }}€</div>
                                    <div class="mt-4 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Conversions</span>
                                            <span class="font-medium">{{ selectedExperiment.results.control_conversions }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Taux de conversion</span>
                                            <span class="font-medium">{{ selectedExperiment.results.control_rate }}%</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Revenu généré</span>
                                            <span class="font-medium">{{ formatCurrency(selectedExperiment.results.control_revenue) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center border-2 border-purple-500">
                                    <div class="text-sm text-purple-600 mb-2">Variante B (Test)</div>
                                    <div class="text-3xl font-bold text-purple-600">{{ selectedExperiment.test_price }}€</div>
                                    <div class="mt-4 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Conversions</span>
                                            <span class="font-medium">{{ selectedExperiment.results.test_conversions }}</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Taux de conversion</span>
                                            <span class="font-medium">{{ selectedExperiment.results.test_rate }}%</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-500">Revenu généré</span>
                                            <span class="font-medium">{{ formatCurrency(selectedExperiment.results.test_revenue) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Statistical Significance -->
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <h4 class="font-medium text-blue-800 dark:text-blue-300 mb-3">Analyse statistique</h4>
                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <div class="text-gray-500">Confiance statistique</div>
                                        <div class="text-lg font-bold" :class="selectedExperiment.results.confidence >= 95 ? 'text-green-600' : 'text-yellow-600'">
                                            {{ selectedExperiment.results.confidence }}%
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Amélioration</div>
                                        <div class="text-lg font-bold" :class="selectedExperiment.results.improvement > 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ selectedExperiment.results.improvement > 0 ? '+' : '' }}{{ selectedExperiment.results.improvement }}%
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">Gagnant</div>
                                        <div class="text-lg font-bold text-purple-600">
                                            Variante {{ selectedExperiment.results.winner || '?' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommendation -->
                            <div v-if="selectedExperiment.results.confidence >= 95" class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <h4 class="font-medium text-green-800 dark:text-green-300 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Recommandation
                                </h4>
                                <p class="mt-2 text-sm text-green-700 dark:text-green-400">
                                    Les résultats sont statistiquement significatifs. Nous recommandons d'adopter la
                                    <strong>Variante {{ selectedExperiment.results.winner }}</strong> ({{ selectedExperiment.results.winner === 'B' ? selectedExperiment.test_price : selectedExperiment.control_price }}€)
                                    pour toutes les boxes similaires.
                                </p>
                            </div>

                            <div v-else class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <h4 class="font-medium text-yellow-800 dark:text-yellow-300 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    En attente de données
                                </h4>
                                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-400">
                                    Les résultats ne sont pas encore statistiquement significatifs. Continuez l'expérience pour obtenir des données plus fiables.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    experiments: {
        type: Array,
        default: () => [],
    },
    sites: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            active: 0,
            completed: 0,
            total_revenue_impact: 0,
            success_rate: 0,
        }),
    },
});

const showCreateModal = ref(false);
const showResultsModal = ref(false);
const selectedExperiment = ref(null);
const isSubmitting = ref(false);

const newExperiment = ref({
    name: '',
    description: '',
    site_id: '',
    duration_days: 14,
    control_price: 0,
    test_price: 0,
    box_size_category: '',
    traffic_split: 50,
});

const activeExperiments = computed(() =>
    props.experiments.filter(e => ['running', 'paused'].includes(e.status))
);

const completedExperiments = computed(() =>
    props.experiments.filter(e => e.status === 'completed')
);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        running: 'bg-green-100 text-green-800',
        paused: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
        draft: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || classes.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        running: 'En cours',
        paused: 'En pause',
        completed: 'Terminée',
        draft: 'Brouillon',
    };
    return labels[status] || status;
};

const createExperiment = async () => {
    isSubmitting.value = true;
    try {
        router.post(route('tenant.pricing.experiments.create'), newExperiment.value, {
            onSuccess: () => {
                showCreateModal.value = false;
                newExperiment.value = {
                    name: '',
                    description: '',
                    site_id: '',
                    duration_days: 14,
                    control_price: 0,
                    test_price: 0,
                    box_size_category: '',
                    traffic_split: 50,
                };
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } catch (error) {
        console.error('Error creating experiment:', error);
        isSubmitting.value = false;
    }
};

const viewResults = (experiment) => {
    selectedExperiment.value = experiment;
    showResultsModal.value = true;
};

const pauseExperiment = (experiment) => {
    if (confirm('Voulez-vous mettre cette expérience en pause ?')) {
        router.post(route('tenant.pricing.experiments.pause', experiment.id));
    }
};

const resumeExperiment = (experiment) => {
    router.post(route('tenant.pricing.experiments.start', experiment.id));
};

const completeExperiment = (experiment) => {
    if (confirm('Voulez-vous terminer cette expérience et appliquer le gagnant ?')) {
        router.post(route('tenant.pricing.experiments.complete', experiment.id));
    }
};
</script>
