<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    TicketIcon,
    PlusIcon,
    TrashIcon,
    PencilIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    promoCodes: Array,
})

const showCreateModal = ref(false)
const editingCode = ref(null)

const form = useForm({
    code: '',
    name: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 10,
    min_rental_amount: null,
    min_rental_months: null,
    max_uses: null,
    valid_from: null,
    valid_until: null,
    first_time_only: false,
})

const discountTypes = [
    { value: 'percentage', label: 'Pourcentage', unit: '%' },
    { value: 'fixed', label: 'Montant fixe', unit: '€' },
    { value: 'free_months', label: 'Mois gratuits', unit: 'mois' },
]

const createPromoCode = () => {
    form.post(route('tenant.bookings.promo-codes.create'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        },
    })
}

const deletePromoCode = (code) => {
    if (confirm(`Supprimer le code "${code.code}" ?`)) {
        router.delete(route('tenant.bookings.promo-codes.delete', code.id))
    }
}

const toggleActive = (code) => {
    router.patch(route('tenant.bookings.promo-codes.update', code.id), {
        is_active: !code.is_active,
    })
}
</script>

<template>
    <TenantLayout title="Codes promo">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-cyan-600 to-teal-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>

            <div class="max-w-6xl mx-auto relative z-10">
                <Link
                    :href="route('tenant.bookings.index')"
                    class="inline-flex items-center text-teal-100 hover:text-white mb-4 transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour aux réservations
                </Link>

                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <TicketIcon class="h-10 w-10 text-white" />
                        <div>
                            <h1 class="text-3xl font-bold text-white">Codes promo</h1>
                            <p class="mt-1 text-teal-100">Gérez vos codes promotionnels</p>
                        </div>
                    </div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-white text-teal-700 rounded-xl hover:bg-teal-50 transition-colors font-medium"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Nouveau code
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <!-- Empty State -->
            <div v-if="promoCodes.length === 0" class="bg-white rounded-2xl shadow-xl p-12 text-center">
                <TicketIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun code promo</h3>
                <p class="text-gray-500 mb-6">Créez des codes promo pour attirer plus de clients</p>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Créer un code
                </button>
            </div>

            <!-- Promo Codes Table -->
            <div v-else class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Code</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Réduction</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Utilisations</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Validité</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Statut</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="code in promoCodes"
                                :key="code.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-mono font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded inline-block">
                                        {{ code.code }}
                                    </div>
                                    <div class="text-sm text-gray-500 mt-1">{{ code.name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-teal-600">{{ code.discount_label }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-900">{{ code.uses_count }}</span>
                                    <span v-if="code.max_uses" class="text-gray-500"> / {{ code.max_uses }}</span>
                                    <span v-else class="text-gray-500"> / ∞</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div v-if="code.valid_from || code.valid_until">
                                        <span v-if="code.valid_from">Du {{ code.valid_from }}</span>
                                        <span v-if="code.valid_until"> au {{ code.valid_until }}</span>
                                    </div>
                                    <span v-else class="text-gray-400">Illimité</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        @click="toggleActive(code)"
                                        :class="code.is_valid ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                                        class="px-3 py-1 rounded-full text-xs font-medium"
                                    >
                                        {{ code.is_valid ? 'Valide' : 'Expiré/Inactif' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        @click="deletePromoCode(code)"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouveau code promo</h3>
                <form @submit.prevent="createPromoCode" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                            <input
                                type="text"
                                v-model="form.code"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 uppercase"
                                placeholder="PROMO2025"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input
                                type="text"
                                v-model="form.name"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                                placeholder="Promo Noël"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                            placeholder="Description du code promo..."
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type de réduction</label>
                            <select
                                v-model="form.discount_type"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                            >
                                <option v-for="type in discountTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valeur</label>
                            <div class="relative">
                                <input
                                    type="number"
                                    v-model="form.discount_value"
                                    step="0.01"
                                    min="0"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-2 pr-12 focus:ring-2 focus:ring-teal-500"
                                    required
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">
                                    {{ discountTypes.find(t => t.value === form.discount_type)?.unit }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Utilisations max</label>
                            <input
                                type="number"
                                v-model="form.max_uses"
                                min="1"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                                placeholder="Illimité"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location min (mois)</label>
                            <input
                                type="number"
                                v-model="form.min_rental_months"
                                min="1"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                                placeholder="Aucun"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valide à partir du</label>
                            <input
                                type="date"
                                v-model="form.valid_from"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valide jusqu'au</label>
                            <input
                                type="date"
                                v-model="form.valid_until"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500"
                            />
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.first_time_only"
                            id="first_time"
                            class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                        />
                        <label for="first_time" class="ml-2 text-sm text-gray-700">Nouveaux clients uniquement</label>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors disabled:opacity-50"
                        >
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
