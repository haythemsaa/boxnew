<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Changer de Box</h1>
                    <p class="text-gray-600 dark:text-gray-400">Upgrade ou downgrade vers un autre box disponible</p>
                </div>
                <Link
                    :href="route('customer.portal.contracts')"
                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour aux contrats
                </Link>
            </div>

            <!-- Current Box Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-blue-50 dark:bg-blue-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-box text-blue-600 mr-2"></i>
                        Box Actuel
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Code</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ contract.current_box?.code }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Surface</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ contract.current_box?.size_m2 }} m²</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Etage</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ contract.current_box?.floor || 'RDC' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Loyer Mensuel</div>
                            <div class="text-xl font-bold text-blue-600">{{ formatCurrency(contract.monthly_price) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Boxes -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-exchange-alt text-green-600 mr-2"></i>
                        Boxes Disponibles
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Selectionnez un nouveau box pour voir la simulation de changement
                    </p>
                </div>

                <div v-if="availableBoxes.length > 0" class="p-6">
                    <!-- Category Tabs -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <button
                            v-for="(boxes, category) in boxesByCategory"
                            :key="category"
                            @click="selectedCategory = category"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                selectedCategory === category
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                            ]"
                        >
                            {{ getCategoryLabel(category) }} ({{ boxes.length }})
                        </button>
                    </div>

                    <!-- Boxes Grid -->
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="box in filteredBoxes"
                            :key="box.id"
                            @click="selectBox(box)"
                            :class="[
                                'p-4 rounded-xl border-2 cursor-pointer transition',
                                selectedBox?.id === box.id
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700'
                            ]"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ box.code }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ box.name }}</div>
                                </div>
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        box.is_upgrade ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' :
                                        box.is_downgrade ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                    ]"
                                >
                                    {{ box.is_upgrade ? 'Upgrade' : box.is_downgrade ? 'Downgrade' : 'Lateral' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Surface:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1">{{ box.size_m2 }} m²</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Etage:</span>
                                    <span class="font-medium text-gray-900 dark:text-white ml-1">{{ box.floor || 'RDC' }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                <div>
                                    <div class="text-lg font-bold" :class="box.is_upgrade ? 'text-green-600' : box.is_downgrade ? 'text-amber-600' : 'text-gray-900 dark:text-white'">
                                        {{ formatCurrency(box.current_price) }}/mois
                                    </div>
                                </div>
                                <div class="text-sm" :class="box.price_difference > 0 ? 'text-green-600' : box.price_difference < 0 ? 'text-amber-600' : 'text-gray-500'">
                                    {{ box.price_difference > 0 ? '+' : '' }}{{ formatCurrency(box.price_difference) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-12 text-center">
                    <i class="fas fa-box-open text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucun box disponible</h3>
                    <p class="text-gray-500 dark:text-gray-400">Il n'y a pas d'autre box disponible sur ce site pour le moment.</p>
                </div>
            </div>

            <!-- Preview Panel -->
            <div v-if="preview" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-purple-50 dark:bg-purple-900/20">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-calculator text-purple-600 mr-2"></i>
                        Simulation du Changement
                    </h2>
                </div>
                <div class="p-6">
                    <!-- Change Summary -->
                    <div class="grid md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Box Actuel</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ preview.current_box?.code }}</div>
                            <div class="text-blue-600 font-medium">{{ formatCurrency(preview.current_box?.monthly_price) }}/mois</div>
                        </div>
                        <div class="flex items-center justify-center">
                            <i class="fas fa-arrow-right text-3xl text-purple-500"></i>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Nouveau Box</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ preview.new_box?.code }}</div>
                            <div :class="preview.calculation?.is_upgrade ? 'text-green-600' : 'text-amber-600'" class="font-medium">
                                {{ formatCurrency(preview.new_box?.monthly_price) }}/mois
                            </div>
                        </div>
                    </div>

                    <!-- Prorated Calculation -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Calcul au Prorata</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Jours restants ce mois</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ preview.calculation?.days_remaining }} jours</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Credit ancien box (prorata)</span>
                                <span class="font-medium text-green-600">-{{ formatCurrency(preview.calculation?.prorated_credit) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Charge nouveau box (prorata)</span>
                                <span class="font-medium text-amber-600">+{{ formatCurrency(preview.calculation?.prorated_charge) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-blue-200 dark:border-blue-700">
                                <span class="font-semibold text-gray-900 dark:text-white">Montant Net</span>
                                <span
                                    class="font-bold text-lg"
                                    :class="preview.calculation?.net_amount > 0 ? 'text-amber-600' : 'text-green-600'"
                                >
                                    {{ preview.calculation?.net_amount > 0 ? 'A payer: ' : 'Credit: ' }}
                                    {{ formatCurrency(Math.abs(preview.calculation?.net_amount || 0)) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- New Monthly Price -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 mb-6">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 dark:text-gray-300">Nouveau loyer mensuel a partir du mois prochain</span>
                            <span class="text-2xl font-bold text-green-600">{{ formatCurrency(preview.summary?.new_monthly_price) }}/mois</span>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ preview.calculation?.is_upgrade ? 'Augmentation' : 'Reduction' }} de {{ formatCurrency(Math.abs(preview.calculation?.monthly_difference || 0)) }}/mois
                        </div>
                    </div>

                    <!-- Reason Input -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Raison du changement (optionnel)
                        </label>
                        <textarea
                            v-model="changeReason"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Ex: Besoin de plus d'espace, reduction des affaires stockees..."
                        ></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button
                            @click="processChange"
                            :disabled="processing"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition disabled:opacity-50"
                        >
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ processing ? 'Traitement...' : 'Confirmer le Changement' }}
                        </button>
                        <button
                            @click="cancelPreview"
                            class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>

            <!-- Success Modal -->
            <div v-if="showSuccess" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 max-w-md mx-4 text-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ successMessage }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Vous recevrez une confirmation par email.
                    </p>
                    <Link
                        :href="route('customer.portal.contracts')"
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition inline-block"
                    >
                        Retour aux contrats
                    </Link>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    contract: Object,
    availableBoxes: Array,
    boxesByCategory: Object,
});

const selectedCategory = ref(Object.keys(props.boxesByCategory || {})[0] || null);
const selectedBox = ref(null);
const preview = ref(null);
const changeReason = ref('');
const processing = ref(false);
const showSuccess = ref(false);
const successMessage = ref('');

const filteredBoxes = computed(() => {
    if (!selectedCategory.value || !props.boxesByCategory) return props.availableBoxes || [];
    return props.boxesByCategory[selectedCategory.value] || [];
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const getCategoryLabel = (category) => {
    const labels = {
        'XS': 'Tres Petit (XS)',
        'S': 'Petit (S)',
        'M': 'Moyen (M)',
        'L': 'Grand (L)',
        'XL': 'Tres Grand (XL)',
        'XXL': 'Extra Grand (XXL)',
    };
    return labels[category] || category;
};

const selectBox = async (box) => {
    selectedBox.value = box;

    try {
        const response = await fetch(route('customer.portal.contract.change-box.preview', props.contract.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                new_box_id: box.id,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            preview.value = data;
        } else {
            alert(data.error || 'Erreur lors de la simulation');
            preview.value = null;
        }
    } catch (error) {
        console.error('Preview error:', error);
        alert('Erreur de connexion');
        preview.value = null;
    }
};

const cancelPreview = () => {
    selectedBox.value = null;
    preview.value = null;
    changeReason.value = '';
};

const processChange = async () => {
    if (!selectedBox.value) return;

    processing.value = true;

    try {
        const response = await fetch(route('customer.portal.contract.change-box.process', props.contract.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                new_box_id: selectedBox.value.id,
                reason: changeReason.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            successMessage.value = data.message;
            showSuccess.value = true;
        } else {
            alert(data.error || 'Erreur lors du traitement');
        }
    } catch (error) {
        console.error('Process error:', error);
        alert('Erreur de connexion');
    } finally {
        processing.value = false;
    }
};
</script>
