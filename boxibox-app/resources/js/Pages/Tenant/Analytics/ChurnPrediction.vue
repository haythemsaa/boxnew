<template>
    <TenantLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                        <Link href="/tenant/analytics/ai" class="hover:text-gray-700">Analytics IA</Link>
                        <span>/</span>
                        <span>Prediction Churn</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Prediction de Churn</h1>
                    <p class="text-gray-600 mt-1">
                        Identifiez les clients a risque et lancez des actions de retention proactives
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="exportData"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exporter
                    </button>
                    <button
                        @click="refreshData"
                        :disabled="isRefreshing"
                        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 flex items-center gap-2 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Actualiser
                    </button>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl border p-4 shadow-sm">
                    <div class="text-sm text-gray-500 mb-1">Clients actifs</div>
                    <div class="text-2xl font-bold text-gray-900">{{ analysis.summary?.total_active_customers || 0 }}</div>
                </div>
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl border border-red-200 p-4">
                    <div class="text-sm text-red-600 mb-1">Critique</div>
                    <div class="text-2xl font-bold text-red-700">{{ analysis.summary?.critical_count || 0 }}</div>
                </div>
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border border-orange-200 p-4">
                    <div class="text-sm text-orange-600 mb-1">Risque eleve</div>
                    <div class="text-2xl font-bold text-orange-700">{{ analysis.summary?.high_risk_count || 0 }}</div>
                </div>
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl border border-amber-200 p-4">
                    <div class="text-sm text-amber-600 mb-1">Risque moyen</div>
                    <div class="text-2xl font-bold text-amber-700">{{ analysis.summary?.medium_risk_count || 0 }}</div>
                </div>
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl border border-emerald-200 p-4">
                    <div class="text-sm text-emerald-600 mb-1">MRR a risque</div>
                    <div class="text-2xl font-bold text-emerald-700">{{ formatCurrency(analysis.summary?.potential_mrr_loss) }}</div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Risk Distribution Chart -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Distribution des risques</h3>
                    <div class="space-y-3">
                        <div v-for="(data, level) in analysis.risk_distribution" :key="level">
                            <div class="flex justify-between text-sm mb-1">
                                <span :class="getRiskTextClass(level)">{{ getRiskLabel(level) }}</span>
                                <span class="text-gray-600">{{ data.count }} clients</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div
                                    class="h-3 rounded-full transition-all"
                                    :class="getRiskBarClass(level)"
                                    :style="{ width: getPercentage(data.count) + '%' }"
                                ></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">{{ formatCurrency(data.revenue) }}/mois a risque</div>
                        </div>
                    </div>
                </div>

                <!-- Churn Trends -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Evolution du churn</h3>
                    <div class="h-48 flex items-end justify-between gap-2">
                        <div
                            v-for="trend in analysis.trends"
                            :key="trend.month"
                            class="flex-1 flex flex-col items-center"
                        >
                            <div class="text-xs text-gray-500 mb-1">{{ trend.churn_rate }}%</div>
                            <div
                                class="w-full bg-gradient-to-t from-red-500 to-red-300 rounded-t transition-all"
                                :style="{ height: Math.max(10, trend.churn_rate * 10) + 'px' }"
                            ></div>
                            <div class="text-xs text-gray-400 mt-2 truncate w-full text-center">{{ trend.month_label }}</div>
                        </div>
                    </div>
                </div>

                <!-- Top Risk Factors -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Facteurs de churn principaux</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(factor, index) in analysis.top_factors"
                            :key="factor.factor_key"
                            class="flex items-center gap-3"
                        >
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                                :class="{
                                    'bg-red-100 text-red-700': index === 0,
                                    'bg-orange-100 text-orange-700': index === 1,
                                    'bg-amber-100 text-amber-700': index === 2,
                                    'bg-gray-100 text-gray-700': index > 2,
                                }">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">{{ factor.factor }}</div>
                                <div class="text-xs text-gray-500">Score moyen: {{ factor.avg_score }}</div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="{
                                    'bg-red-100 text-red-700': factor.impact === 'high',
                                    'bg-amber-100 text-amber-700': factor.impact === 'medium',
                                    'bg-gray-100 text-gray-600': factor.impact === 'low',
                                }"
                            >
                                {{ factor.impact === 'high' ? 'Impact fort' : factor.impact === 'medium' ? 'Impact moyen' : 'Impact faible' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- At-Risk Customers Table -->
            <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
                <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Clients a risque</h3>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="filterRiskLevel"
                            class="text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="all">Tous les niveaux</option>
                            <option value="critical">Critique</option>
                            <option value="high">Eleve</option>
                            <option value="medium">Moyen</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Probabilite</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Risque</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MRR</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fin contrat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facteurs</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="customer in filteredCustomers"
                                :key="customer.customer_id"
                                class="hover:bg-gray-50 cursor-pointer"
                                @click="showCustomerDetail(customer)"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white font-medium"
                                            :class="getRiskBgClass(customer.risk_level)">
                                            {{ getInitials(customer.customer_name) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ customer.customer_name }}</div>
                                            <div class="text-sm text-gray-500">{{ customer.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                            <div
                                                class="h-2 rounded-full"
                                                :class="getRiskBarClass(customer.risk_level)"
                                                :style="{ width: customer.probability + '%' }"
                                            ></div>
                                        </div>
                                        <span class="font-semibold" :class="getRiskTextClass(customer.risk_level)">
                                            {{ customer.probability }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 text-xs font-medium rounded-full"
                                        :class="getRiskBadgeClass(customer.risk_level)"
                                    >
                                        {{ getRiskLabel(customer.risk_level) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ formatCurrency(customer.monthly_revenue) }}</div>
                                    <div class="text-xs text-gray-500">{{ formatCurrency(customer.monthly_revenue * 12) }}/an</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ customer.contract_end_date || 'N/A' }}</div>
                                    <div class="text-xs" :class="getDaysClass(customer.days_until_expiry)">
                                        {{ customer.days_until_expiry !== null ? customer.days_until_expiry + ' jours' : 'Indefini' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="factor in customer.top_risk_factors?.slice(0, 2)"
                                            :key="factor.factor"
                                            class="px-2 py-0.5 text-xs rounded"
                                            :class="{
                                                'bg-red-100 text-red-700': factor.impact === 'high',
                                                'bg-amber-100 text-amber-700': factor.impact === 'medium',
                                                'bg-gray-100 text-gray-600': factor.impact === 'low',
                                            }"
                                        >
                                            {{ factor.factor }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click.stop="contactCustomer(customer, 'phone')"
                                            class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg"
                                            title="Appeler"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click.stop="contactCustomer(customer, 'email')"
                                            class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg"
                                            title="Envoyer email"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click.stop="showRetentionOffer(customer)"
                                            class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg"
                                            title="Offre retention"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredCustomers.length === 0" class="p-12 text-center">
                    <div class="text-5xl mb-4">🎉</div>
                    <div class="text-lg font-medium text-gray-900 mb-2">Aucun client a risque</div>
                    <div class="text-gray-500">Tous vos clients sont en bonne sante !</div>
                </div>
            </div>

            <!-- Customer Detail Modal -->
            <div v-if="selectedCustomer" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b sticky top-0 bg-white z-10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-medium"
                                    :class="getRiskBgClass(selectedCustomer.risk_level)">
                                    {{ getInitials(selectedCustomer.customer_name) }}
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">{{ selectedCustomer.customer_name }}</h3>
                                    <p class="text-gray-500">{{ selectedCustomer.email }}</p>
                                </div>
                            </div>
                            <button @click="selectedCustomer = null" class="p-2 hover:bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Risk Score -->
                        <div class="flex items-center justify-between p-4 rounded-xl" :class="getRiskBgLightClass(selectedCustomer.risk_level)">
                            <div>
                                <div class="text-sm" :class="getRiskTextClass(selectedCustomer.risk_level)">Score de risque</div>
                                <div class="text-3xl font-bold" :class="getRiskTextClass(selectedCustomer.risk_level)">
                                    {{ selectedCustomer.probability }}%
                                </div>
                            </div>
                            <span
                                class="px-4 py-2 text-sm font-medium rounded-full"
                                :class="getRiskBadgeClass(selectedCustomer.risk_level)"
                            >
                                {{ getRiskLabel(selectedCustomer.risk_level) }}
                            </span>
                        </div>

                        <!-- Key Metrics -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ formatCurrency(selectedCustomer.monthly_revenue) }}</div>
                                <div class="text-sm text-gray-500">MRR</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ selectedCustomer.tenure_months }}</div>
                                <div class="text-sm text-gray-500">Mois d'anciennete</div>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold" :class="getDaysClass(selectedCustomer.days_until_expiry)">
                                    {{ selectedCustomer.days_until_expiry ?? 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500">Jours avant fin contrat</div>
                            </div>
                        </div>

                        <!-- Risk Factors -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">Facteurs de risque identifies</h4>
                            <div class="space-y-3">
                                <div
                                    v-for="factor in selectedCustomer.top_risk_factors"
                                    :key="factor.factor"
                                    class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg"
                                >
                                    <div
                                        class="w-2 h-2 rounded-full"
                                        :class="{
                                            'bg-red-500': factor.impact === 'high',
                                            'bg-amber-500': factor.impact === 'medium',
                                            'bg-gray-400': factor.impact === 'low',
                                        }"
                                    ></div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">{{ factor.factor }}</div>
                                        <div class="text-sm text-gray-500">Score: {{ factor.score }}/100</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recommended Actions -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3">Actions recommandees</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="(action, index) in selectedCustomer.recommended_actions"
                                    :key="index"
                                    class="flex items-start gap-3 p-3 border rounded-lg"
                                    :class="{
                                        'border-red-200 bg-red-50': action.priority === 'urgent',
                                        'border-orange-200 bg-orange-50': action.priority === 'high',
                                        'border-amber-200 bg-amber-50': action.priority === 'medium',
                                    }"
                                >
                                    <span
                                        class="px-2 py-0.5 text-xs font-medium rounded"
                                        :class="{
                                            'bg-red-200 text-red-800': action.priority === 'urgent',
                                            'bg-orange-200 text-orange-800': action.priority === 'high',
                                            'bg-amber-200 text-amber-800': action.priority === 'medium',
                                        }"
                                    >
                                        {{ action.priority === 'urgent' ? 'Urgent' : action.priority === 'high' ? 'Prioritaire' : 'Normal' }}
                                    </span>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ action.action }}</div>
                                        <div class="text-sm text-gray-600">{{ action.description }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Retention Offer -->
                        <div v-if="selectedCustomer.retention_offer" class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4">
                            <h4 class="font-semibold text-emerald-800 mb-2">Offre de retention suggeree</h4>
                            <div class="flex items-center gap-6">
                                <div>
                                    <div class="text-3xl font-bold text-emerald-700">-{{ selectedCustomer.retention_offer.discount_percent }}%</div>
                                    <div class="text-sm text-emerald-600">sur {{ selectedCustomer.retention_offer.free_months }} mois</div>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm text-emerald-700 font-medium mb-1">Avantages inclus:</div>
                                    <ul class="text-sm text-emerald-600 space-y-1">
                                        <li v-for="perk in selectedCustomer.retention_offer.additional_perks" :key="perk">
                                            • {{ perk }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Urgency -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <div class="text-sm text-gray-500">Urgence de contact</div>
                                <div class="font-semibold text-gray-900 capitalize">{{ selectedCustomer.contact_urgency?.level }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Deadline</div>
                                <div class="font-semibold text-gray-900">{{ selectedCustomer.contact_urgency?.deadline }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Canal recommande</div>
                                <div class="font-semibold text-gray-900 capitalize">{{ selectedCustomer.contact_urgency?.channel }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t bg-gray-50 flex justify-end gap-3">
                        <button
                            @click="selectedCustomer = null"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100"
                        >
                            Fermer
                        </button>
                        <button
                            @click="createRetentionTask(selectedCustomer)"
                            class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700"
                        >
                            Creer tache de retention
                        </button>
                    </div>
                </div>
            </div>

            <!-- Model Accuracy -->
            <div class="bg-white rounded-xl border shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Precision du modele</h3>
                    <span class="text-sm text-gray-500">Derniere mise a jour: {{ analysis.model_accuracy?.last_updated }}</span>
                </div>
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ analysis.model_accuracy?.overall }}%</div>
                        <div class="text-sm text-gray-500">Precision globale</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ analysis.model_accuracy?.precision }}%</div>
                        <div class="text-sm text-gray-500">Precision</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-emerald-600">{{ analysis.model_accuracy?.recall }}%</div>
                        <div class="text-sm text-gray-500">Rappel</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-amber-600">{{ analysis.model_accuracy?.f1_score }}%</div>
                        <div class="text-sm text-gray-500">Score F1</div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    analysis: {
        type: Object,
        required: true,
    },
});

const isRefreshing = ref(false);
const filterRiskLevel = ref('all');
const selectedCustomer = ref(null);

const filteredCustomers = computed(() => {
    const predictions = props.analysis.predictions || [];
    if (filterRiskLevel.value === 'all') {
        return predictions.filter(c => c.risk_level !== 'low');
    }
    return predictions.filter(c => c.risk_level === filterRiskLevel.value);
});

const getPercentage = (count) => {
    const total = props.analysis.summary?.total_active_customers || 1;
    return Math.round((count / total) * 100);
};

const formatCurrency = (value) => {
    if (!value) return '0 EUR';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(value);
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

const getRiskLabel = (level) => {
    const labels = { critical: 'Critique', high: 'Eleve', medium: 'Moyen', low: 'Faible' };
    return labels[level] || level;
};

const getRiskTextClass = (level) => {
    const classes = {
        critical: 'text-red-600',
        high: 'text-orange-600',
        medium: 'text-amber-600',
        low: 'text-emerald-600',
    };
    return classes[level] || 'text-gray-600';
};

const getRiskBarClass = (level) => {
    const classes = {
        critical: 'bg-red-500',
        high: 'bg-orange-500',
        medium: 'bg-amber-500',
        low: 'bg-emerald-500',
    };
    return classes[level] || 'bg-gray-500';
};

const getRiskBgClass = (level) => {
    const classes = {
        critical: 'bg-red-500',
        high: 'bg-orange-500',
        medium: 'bg-amber-500',
        low: 'bg-emerald-500',
    };
    return classes[level] || 'bg-gray-500';
};

const getRiskBgLightClass = (level) => {
    const classes = {
        critical: 'bg-red-50',
        high: 'bg-orange-50',
        medium: 'bg-amber-50',
        low: 'bg-emerald-50',
    };
    return classes[level] || 'bg-gray-50';
};

const getRiskBadgeClass = (level) => {
    const classes = {
        critical: 'bg-red-100 text-red-800',
        high: 'bg-orange-100 text-orange-800',
        medium: 'bg-amber-100 text-amber-800',
        low: 'bg-emerald-100 text-emerald-800',
    };
    return classes[level] || 'bg-gray-100 text-gray-800';
};

const getDaysClass = (days) => {
    if (days === null || days === undefined) return 'text-gray-500';
    if (days <= 7) return 'text-red-600';
    if (days <= 30) return 'text-orange-600';
    return 'text-gray-500';
};

const refreshData = async () => {
    isRefreshing.value = true;
    router.reload({ only: ['analysis'], onFinish: () => isRefreshing.value = false });
};

const exportData = () => {
    window.location.href = '/tenant/analytics/churn/export';
};

const showCustomerDetail = (customer) => {
    selectedCustomer.value = customer;
};

const contactCustomer = (customer, method) => {
    if (method === 'phone' && customer.phone) {
        window.location.href = `tel:${customer.phone}`;
    } else if (method === 'email') {
        window.location.href = `mailto:${customer.email}`;
    }
};

const showRetentionOffer = (customer) => {
    selectedCustomer.value = customer;
};

const createRetentionTask = (customer) => {
    // Create a retention task
    router.post('/tenant/tasks', {
        type: 'retention',
        title: `Retention: ${customer.customer_name}`,
        description: `Client a risque ${customer.risk_level} (${customer.probability}%). Actions: ${customer.recommended_actions?.map(a => a.action).join(', ')}`,
        priority: customer.risk_level === 'critical' ? 'urgent' : 'high',
        related_type: 'customer',
        related_id: customer.customer_id,
    });
    selectedCustomer.value = null;
};
</script>
