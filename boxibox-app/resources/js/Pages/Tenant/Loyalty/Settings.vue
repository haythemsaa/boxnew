<template>
    <TenantLayout title="Configuration fidélité" :breadcrumbs="[{ label: 'Fidélité', href: route('tenant.loyalty.index') }, { label: 'Configuration' }]">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Program Basics -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du programme</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom du programme</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="Programme fidélité BoxiBox"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="Description du programme pour vos clients..."
                                ></textarea>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                                <span class="text-sm text-gray-700">Programme actif</span>
                            </div>
                        </div>
                    </div>

                    <!-- Points Configuration -->
                    <div class="border-t border-gray-100 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuration des points</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Points par euro dépensé</label>
                                <input
                                    v-model="form.points_per_euro"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">Nombre de points gagnés pour chaque euro</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum pour échange</label>
                                <input
                                    v-model="form.min_points_redeem"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">Points minimum pour utiliser une récompense</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Valeur d'un point (€)</label>
                                <input
                                    v-model="form.point_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">Valeur monétaire d'un point</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expiration des points (mois)</label>
                                <input
                                    v-model="form.points_expiry_months"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">0 = pas d'expiration</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bonus Points -->
                    <div class="border-t border-gray-100 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Points bonus</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bonus de bienvenue</label>
                                <input
                                    v-model="form.welcome_bonus"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">Points offerts à l'inscription</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bonus de parrainage</label>
                                <input
                                    v-model="form.referral_bonus"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">Points pour le parrain</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bonus filleul</label>
                                <input
                                    v-model="form.referral_bonus_referred"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">Points pour le filleul</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Bonus anniversaire</label>
                                <input
                                    v-model="form.birthday_bonus"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200"
                                />
                                <p class="text-xs text-gray-500 mt-1">Points offerts le jour de l'anniversaire</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tier Configuration -->
                    <div class="border-t border-gray-100 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Niveaux de fidélité</h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-4 gap-4">
                                <div class="p-4 bg-amber-50 rounded-xl border border-amber-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-4 h-4 rounded-full bg-amber-600"></div>
                                        <span class="font-medium text-amber-900">Bronze</span>
                                    </div>
                                    <input
                                        v-model="form.tier_thresholds.bronze"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border-amber-200 text-sm"
                                        placeholder="0"
                                    />
                                    <p class="text-xs text-amber-700 mt-1">points min.</p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-4 h-4 rounded-full bg-gray-400"></div>
                                        <span class="font-medium text-gray-900">Argent</span>
                                    </div>
                                    <input
                                        v-model="form.tier_thresholds.silver"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border-gray-200 text-sm"
                                        placeholder="500"
                                    />
                                    <p class="text-xs text-gray-600 mt-1">points min.</p>
                                </div>
                                <div class="p-4 bg-yellow-50 rounded-xl border border-yellow-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                                        <span class="font-medium text-yellow-900">Or</span>
                                    </div>
                                    <input
                                        v-model="form.tier_thresholds.gold"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border-yellow-200 text-sm"
                                        placeholder="2000"
                                    />
                                    <p class="text-xs text-yellow-700 mt-1">points min.</p>
                                </div>
                                <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-4 h-4 rounded-full bg-indigo-600"></div>
                                        <span class="font-medium text-indigo-900">Platine</span>
                                    </div>
                                    <input
                                        v-model="form.tier_thresholds.platinum"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border-indigo-200 text-sm"
                                        placeholder="5000"
                                    />
                                    <p class="text-xs text-indigo-700 mt-1">points min.</p>
                                </div>
                            </div>

                            <div class="p-4 bg-blue-50 rounded-xl">
                                <h4 class="font-medium text-blue-900 mb-2">Multiplicateurs par niveau</h4>
                                <div class="grid grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <label class="text-blue-700">Bronze</label>
                                        <input
                                            v-model="form.tier_multipliers.bronze"
                                            type="number"
                                            step="0.1"
                                            min="1"
                                            class="w-full rounded-lg border-blue-200 mt-1"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-blue-700">Argent</label>
                                        <input
                                            v-model="form.tier_multipliers.silver"
                                            type="number"
                                            step="0.1"
                                            min="1"
                                            class="w-full rounded-lg border-blue-200 mt-1"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-blue-700">Or</label>
                                        <input
                                            v-model="form.tier_multipliers.gold"
                                            type="number"
                                            step="0.1"
                                            min="1"
                                            class="w-full rounded-lg border-blue-200 mt-1"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-blue-700">Platine</label>
                                        <input
                                            v-model="form.tier_multipliers.platinum"
                                            type="number"
                                            step="0.1"
                                            min="1"
                                            class="w-full rounded-lg border-blue-200 mt-1"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('tenant.loyalty.index')" class="btn-secondary">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <span v-if="form.processing">Enregistrement...</span>
                            <span v-else>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    program: Object,
})

const form = useForm({
    name: props.program?.name || 'Programme fidélité',
    description: props.program?.description || '',
    is_active: props.program?.is_active ?? true,
    points_per_euro: props.program?.points_per_euro || 10,
    min_points_redeem: props.program?.min_points_redeem || 100,
    point_value: props.program?.point_value || 0.01,
    points_expiry_months: props.program?.points_expiry_months || 24,
    welcome_bonus: props.program?.welcome_bonus || 50,
    referral_bonus: props.program?.referral_bonus || 100,
    referral_bonus_referred: props.program?.referral_bonus_referred || 50,
    birthday_bonus: props.program?.birthday_bonus || 25,
    tier_thresholds: {
        bronze: props.program?.tier_thresholds?.bronze || 0,
        silver: props.program?.tier_thresholds?.silver || 500,
        gold: props.program?.tier_thresholds?.gold || 2000,
        platinum: props.program?.tier_thresholds?.platinum || 5000,
    },
    tier_multipliers: {
        bronze: props.program?.tier_multipliers?.bronze || 1.0,
        silver: props.program?.tier_multipliers?.silver || 1.25,
        gold: props.program?.tier_multipliers?.gold || 1.5,
        platinum: props.program?.tier_multipliers?.platinum || 2.0,
    },
})

const submit = () => {
    if (props.program?.id) {
        form.put(route('tenant.loyalty.settings.update'))
    } else {
        form.post(route('tenant.loyalty.settings.store'))
    }
}
</script>
