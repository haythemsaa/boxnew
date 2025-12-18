<template>
    <SuperAdminLayout title="Packs de Credits">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <Link :href="route('superadmin.credits.index')" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                            <i class="fas fa-arrow-left mr-1"></i> Retour aux credits
                        </Link>
                        <h2 class="text-3xl font-bold text-gray-900">Packs de Credits</h2>
                        <p class="mt-1 text-sm text-gray-600">Gerez les packs Email/SMS disponibles a l'achat</p>
                    </div>
                    <div class="flex space-x-3">
                        <button @click="seedDefaults"
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                            <i class="fas fa-magic mr-2"></i>Packs par defaut
                        </button>
                        <button @click="openCreateModal"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Nouveau Pack
                        </button>
                    </div>
                </div>

                <!-- Packs Email -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-envelope text-blue-500 mr-2"></i>Packs Email
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="pack in emailPacks" :key="pack.id"
                            class="bg-white rounded-lg shadow overflow-hidden"
                            :class="!pack.is_active ? 'opacity-60' : ''">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <h4 class="font-semibold text-gray-900">{{ pack.name }}</h4>
                                    <span v-if="!pack.is_active" class="text-xs text-gray-500">Inactif</span>
                                </div>
                                <div class="text-center mb-4">
                                    <span class="text-3xl font-bold text-blue-600">{{ formatNumber(pack.credits) }}</span>
                                    <span class="text-gray-500"> emails</span>
                                </div>
                                <div class="text-center mb-4">
                                    <span class="text-2xl font-bold text-gray-900">{{ pack.price }}</span>
                                    <span class="text-gray-600">{{ pack.currency }}</span>
                                </div>
                                <div class="text-center text-sm text-gray-500 mb-4">
                                    {{ pack.price_per_unit ? (pack.price_per_unit * 100).toFixed(2) : '0' }} centimes/email
                                </div>
                                <div class="text-center text-xs text-gray-500">
                                    Validite: {{ pack.validity_days ? pack.validity_days + ' jours' : 'Illimitee' }}
                                </div>
                            </div>
                            <div class="px-6 py-3 bg-gray-50 border-t flex justify-between">
                                <button @click="editPack(pack)" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="togglePack(pack)"
                                    :class="pack.is_active ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'">
                                    <i :class="pack.is_active ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                </button>
                                <button @click="deletePack(pack)" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Packs SMS -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-sms text-green-500 mr-2"></i>Packs SMS
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="pack in smsPacks" :key="pack.id"
                            class="bg-white rounded-lg shadow overflow-hidden"
                            :class="!pack.is_active ? 'opacity-60' : ''">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <h4 class="font-semibold text-gray-900">{{ pack.name }}</h4>
                                    <span v-if="!pack.is_active" class="text-xs text-gray-500">Inactif</span>
                                </div>
                                <div class="text-center mb-4">
                                    <span class="text-3xl font-bold text-green-600">{{ formatNumber(pack.credits) }}</span>
                                    <span class="text-gray-500"> SMS</span>
                                </div>
                                <div class="text-center mb-4">
                                    <span class="text-2xl font-bold text-gray-900">{{ pack.price }}</span>
                                    <span class="text-gray-600">{{ pack.currency }}</span>
                                </div>
                                <div class="text-center text-sm text-gray-500 mb-4">
                                    {{ pack.price_per_unit ? (pack.price_per_unit * 100).toFixed(2) : '0' }} centimes/SMS
                                </div>
                                <div class="text-center text-xs text-gray-500">
                                    Validite: {{ pack.validity_days ? pack.validity_days + ' jours' : 'Illimitee' }}
                                </div>
                            </div>
                            <div class="px-6 py-3 bg-gray-50 border-t flex justify-between">
                                <button @click="editPack(pack)" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="togglePack(pack)"
                                    :class="pack.is_active ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'">
                                    <i :class="pack.is_active ? 'fas fa-toggle-on' : 'fas fa-toggle-off'"></i>
                                </button>
                                <button @click="deletePack(pack)" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Create/Edit -->
                <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                        <h3 class="text-lg font-semibold mb-4">
                            {{ editingPack ? 'Modifier le pack' : 'Nouveau pack' }}
                        </h3>
                        <form @submit.prevent="submitPack">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                                    <input type="text" v-model="packForm.name" required
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="ex: Pack 1000 Emails">
                                </div>

                                <div v-if="!editingPack">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                                    <select v-model="packForm.type" required
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="email">Email</option>
                                        <option value="sms">SMS</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Credits *</label>
                                        <input type="number" v-model="packForm.credits" required min="1"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Prix (EUR) *</label>
                                        <input type="number" v-model="packForm.price" required min="0" step="0.01"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Devise</label>
                                        <select v-model="packForm.currency"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                            <option value="EUR">EUR</option>
                                            <option value="USD">USD</option>
                                            <option value="GBP">GBP</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Validite (jours)</label>
                                        <input type="number" v-model="packForm.validity_days" min="1"
                                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                            placeholder="Laisser vide = illimite">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                                    <input type="number" v-model="packForm.sort_order" min="0"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <label class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" v-model="packForm.is_active"
                                        class="h-5 w-5 text-blue-600 rounded">
                                    <span class="font-medium text-gray-900">Pack actif</span>
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button" @click="closeModal"
                                    class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">
                                    Annuler
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    {{ editingPack ? 'Modifier' : 'Creer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    packs: Array,
});

const showModal = ref(false);
const editingPack = ref(null);

const packForm = useForm({
    name: '',
    type: 'email',
    credits: 1000,
    price: 10,
    currency: 'EUR',
    validity_days: 365,
    is_active: true,
    sort_order: 0,
});

const emailPacks = computed(() => props.packs.filter(p => p.type === 'email'));
const smsPacks = computed(() => props.packs.filter(p => p.type === 'sms'));

const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('fr-FR').format(num);
};

const openCreateModal = () => {
    editingPack.value = null;
    packForm.reset();
    packForm.type = 'email';
    packForm.credits = 1000;
    packForm.price = 10;
    packForm.currency = 'EUR';
    packForm.validity_days = 365;
    packForm.is_active = true;
    showModal.value = true;
};

const editPack = (pack) => {
    editingPack.value = pack;
    packForm.name = pack.name;
    packForm.type = pack.type;
    packForm.credits = pack.credits;
    packForm.price = pack.price;
    packForm.currency = pack.currency;
    packForm.validity_days = pack.validity_days;
    packForm.is_active = pack.is_active;
    packForm.sort_order = pack.sort_order || 0;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingPack.value = null;
};

const submitPack = () => {
    if (editingPack.value) {
        packForm.put(route('superadmin.credits.packs.update', editingPack.value.id), {
            onSuccess: () => closeModal()
        });
    } else {
        packForm.post(route('superadmin.credits.packs.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const togglePack = (pack) => {
    router.put(route('superadmin.credits.packs.update', pack.id), {
        ...pack,
        is_active: !pack.is_active,
    });
};

const deletePack = (pack) => {
    if (confirm(`Supprimer le pack "${pack.name}" ?`)) {
        router.delete(route('superadmin.credits.packs.destroy', pack.id));
    }
};

const seedDefaults = () => {
    if (confirm('Creer/reinitialiser les packs par defaut ?')) {
        router.post(route('superadmin.credits.packs.seed'));
    }
};
</script>
