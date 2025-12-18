<template>
    <TenantLayout>
        <Head title="Membres du Programme" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('tenant.loyalty.index')"
                          class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Membres du Programme</h1>
                        <p class="text-gray-500 dark:text-gray-400">{{ members.total }} membres inscrits</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" v-model="search" @input="debouncedSearch"
                                   placeholder="Rechercher par nom ou email..."
                                   class="w-full pl-10 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button v-for="tier in tierOptions" :key="tier.value"
                                @click="filterByTier(tier.value)"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2',
                                    selectedTier === tier.value
                                        ? tier.activeClass
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                ]">
                            <span>{{ tier.icon }}</span>
                            <span class="hidden sm:inline">{{ tier.label }}</span>
                        </button>
                        <button v-if="selectedTier" @click="clearFilter"
                                class="px-3 py-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Members Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="members.data.length === 0" class="p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Aucun membre trouvé</p>
                </div>

                <table v-else class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Niveau
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Solde actuel
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total gagné
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total utilisé
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="member in members.data" :key="member.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-gray-600 dark:text-gray-300 font-medium">
                                        {{ getInitials(member.customer) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ member.customer?.first_name }} {{ member.customer?.last_name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ member.customer?.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getTierBadgeClass(member.current_tier)">
                                    {{ getTierIcon(member.current_tier) }} {{ getTierLabel(member.current_tier) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ formatNumber(member.current_balance) }}
                                </span>
                                <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">pts</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-green-600 dark:text-green-400">
                                    +{{ formatNumber(member.total_points_earned) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-red-600 dark:text-red-400">
                                    -{{ formatNumber(member.total_points_redeemed) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link :href="route('tenant.loyalty.members.show', member.id)"
                                          class="p-2 text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <button @click="openAdjustModal(member)"
                                            class="p-2 text-gray-500 hover:text-amber-600 dark:hover:text-amber-400 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="members.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Affichage {{ members.from }} à {{ members.to }} sur {{ members.total }}
                        </p>
                        <div class="flex gap-2">
                            <Link v-for="link in members.links" :key="link.label"
                                  :href="link.url || '#'"
                                  :class="[
                                      'px-3 py-1 rounded text-sm',
                                      link.active
                                          ? 'bg-primary-600 text-white'
                                          : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600',
                                      !link.url && 'opacity-50 cursor-not-allowed'
                                  ]"
                                  v-html="link.label">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Points Modal -->
        <div v-if="showAdjustModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showAdjustModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Ajuster les points
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        {{ selectedMember?.customer?.first_name }} {{ selectedMember?.customer?.last_name }}
                        <br>
                        Solde actuel: <strong>{{ formatNumber(selectedMember?.current_balance) }}</strong> pts
                    </p>

                    <form @submit.prevent="submitAdjustment">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Points à ajouter/retirer
                                </label>
                                <input type="number" v-model="adjustForm.points"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="Ex: 100 ou -50">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Utilisez un nombre négatif pour retirer des points
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Raison
                                </label>
                                <input type="text" v-model="adjustForm.reason"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="Ex: Bonus fidélité, Correction...">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showAdjustModal = false"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Annuler
                            </button>
                            <button type="submit"
                                    :disabled="adjustForm.processing"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition disabled:opacity-50">
                                Appliquer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ref } from 'vue';

// Simple debounce implementation
const debounce = (fn, delay) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    members: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedTier = ref(props.filters?.tier || '');
const showAdjustModal = ref(false);
const selectedMember = ref(null);

const tierOptions = [
    { value: 'bronze', label: 'Bronze', icon: '🥉', activeClass: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' },
    { value: 'silver', label: 'Argent', icon: '🥈', activeClass: 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200' },
    { value: 'gold', label: 'Or', icon: '🥇', activeClass: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
    { value: 'platinum', label: 'Platine', icon: '💎', activeClass: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' },
];

const adjustForm = useForm({
    points: null,
    reason: '',
});

const applyFilters = () => {
    router.get(route('tenant.loyalty.members'), {
        search: search.value || undefined,
        tier: selectedTier.value || undefined,
    }, { preserveState: true });
};

const debouncedSearch = debounce(applyFilters, 300);

const filterByTier = (tier) => {
    selectedTier.value = selectedTier.value === tier ? '' : tier;
    applyFilters();
};

const clearFilter = () => {
    selectedTier.value = '';
    applyFilters();
};

const openAdjustModal = (member) => {
    selectedMember.value = member;
    adjustForm.reset();
    showAdjustModal.value = true;
};

const submitAdjustment = () => {
    adjustForm.post(route('tenant.loyalty.members.adjust', selectedMember.value.id), {
        onSuccess: () => {
            showAdjustModal.value = false;
        },
    });
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num || 0);
};

const getInitials = (customer) => {
    if (!customer) return '?';
    return `${customer.first_name?.[0] || ''}${customer.last_name?.[0] || ''}`.toUpperCase();
};

const getTierLabel = (tier) => {
    const labels = { bronze: 'Bronze', silver: 'Argent', gold: 'Or', platinum: 'Platine' };
    return labels[tier] || tier;
};

const getTierIcon = (tier) => {
    const icons = { bronze: '🥉', silver: '🥈', gold: '🥇', platinum: '💎' };
    return icons[tier] || '⭐';
};

const getTierBadgeClass = (tier) => {
    const classes = {
        bronze: 'px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        silver: 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        gold: 'px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        platinum: 'px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return classes[tier] || classes.bronze;
};
</script>
