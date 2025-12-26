<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ArrowLeftIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    sites: Array,
    boxes: Array,
    providers: Object,
});

const form = useForm({
    box_id: '',
    provider: 'generic',
    device_id: '',
    device_name: '',
});

const selectedSite = ref('');

const filteredBoxes = computed(() => {
    if (!selectedSite.value) return props.boxes;
    return props.boxes.filter(box => box.site_id == selectedSite.value);
});

const selectedBox = computed(() => {
    return props.boxes.find(b => b.id == form.box_id);
});

const submit = () => {
    form.post(route('tenant.iot.locks.store'));
};

// Auto-generate device name when box is selected
const onBoxChange = () => {
    if (selectedBox.value && !form.device_name) {
        form.device_name = 'Serrure ' + (selectedBox.value.display_name || selectedBox.value.box_number);
    }
};
</script>

<template>
    <TenantLayout title="Ajouter une serrure">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('tenant.iot.locks.index')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ajouter une serrure</h1>
                    <p class="text-gray-600 dark:text-gray-400">Configurez une nouvelle serrure connectee</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Box Selection -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <LockClosedIcon class="w-5 h-5" />
                        Emplacement
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Filtrer par site
                            </label>
                            <select v-model="selectedSite" class="input">
                                <option value="">Tous les sites</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Box *
                            </label>
                            <select v-model="form.box_id" @change="onBoxChange" class="input" required>
                                <option value="">Selectionner un box</option>
                                <option v-for="box in filteredBoxes" :key="box.id" :value="box.id">
                                    {{ box.display_name || box.box_number }}
                                </option>
                            </select>
                            <p v-if="form.errors.box_id" class="mt-1 text-sm text-red-600">{{ form.errors.box_id }}</p>
                            <p v-if="filteredBoxes.length === 0" class="mt-1 text-sm text-orange-600">
                                Aucun box disponible (tous ont deja une serrure)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Lock Configuration -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Configuration</h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Fournisseur *
                            </label>
                            <select v-model="form.provider" class="input" required>
                                <option v-for="(label, value) in providers" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <p v-if="form.errors.provider" class="mt-1 text-sm text-red-600">{{ form.errors.provider }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ID de l'appareil *
                            </label>
                            <input v-model="form.device_id" type="text" class="input"
                                   placeholder="Ex: NOKE-ABC123, MAC address..." required />
                            <p v-if="form.errors.device_id" class="mt-1 text-sm text-red-600">{{ form.errors.device_id }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                Identifiant unique fourni par le fabricant (numero de serie, MAC, etc.)
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nom de la serrure
                            </label>
                            <input v-model="form.device_name" type="text" class="input"
                                   placeholder="Ex: Serrure Box A12" />
                            <p v-if="form.errors.device_name" class="mt-1 text-sm text-red-600">{{ form.errors.device_name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Provider Info -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                    <h3 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Information sur les fournisseurs</h3>
                    <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                        <li><strong>Noke:</strong> Serrures Bluetooth Noke - Necessitent l'app Noke Pro</li>
                        <li><strong>Salto KS:</strong> Serrures cloud Salto - Integration via API</li>
                        <li><strong>Kisi:</strong> Controle d'acces Kisi - Integration webhook</li>
                        <li><strong>PTI:</strong> Systeme PTI StorLogix - Integration directe</li>
                        <li><strong>Generique:</strong> Autres serrures avec API personnalisee</li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('tenant.iot.locks.index')" class="btn-secondary">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing || !form.box_id" class="btn-primary">
                        <span v-if="form.processing">Creation...</span>
                        <span v-else>Creer la serrure</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium; }
.btn-secondary { @apply px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 font-medium; }
</style>
