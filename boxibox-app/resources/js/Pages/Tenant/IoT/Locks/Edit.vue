<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ArrowLeftIcon, LockClosedIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    lock: Object,
    providers: Object,
});

const form = useForm({
    provider: props.lock.provider || 'generic',
    device_id: props.lock.device_id || '',
    device_name: props.lock.device_name || '',
    status: props.lock.status || 'active',
});

const showDeleteModal = ref(false);

const submit = () => {
    form.put(route('tenant.iot.locks.update', props.lock.id));
};

const deleteLock = () => {
    router.delete(route('tenant.iot.locks.destroy', props.lock.id));
};

const unlockLock = () => {
    if (confirm('Deverrouiller cette serrure ?')) {
        router.post(route('tenant.iot.locks.unlock', props.lock.id));
    }
};

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        offline: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <TenantLayout :title="`Modifier ${lock.device_name}`">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('tenant.iot.locks.index')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ lock.device_name }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', getStatusColor(lock.status)]">
                                {{ lock.status === 'active' ? 'Actif' : lock.status === 'offline' ? 'Hors ligne' : 'Inactif' }}
                            </span>
                            <span class="text-sm text-gray-500">Box {{ lock.box?.box_number }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="unlockLock" class="btn-success" :disabled="lock.status !== 'active'">
                        Deverrouiller
                    </button>
                    <button @click="showDeleteModal = true" class="btn-danger">
                        <TrashIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Lock Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Informations</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Box</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ lock.box?.box_number }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Site</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ lock.box?.site?.name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Batterie</p>
                        <p class="font-medium" :class="lock.battery_level > 20 ? 'text-green-600' : 'text-red-600'">
                            {{ lock.battery_level }}%
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Derniere activite</p>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ lock.last_seen_at ? new Date(lock.last_seen_at).toLocaleString('fr-FR') : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Lock Configuration -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <LockClosedIcon class="w-5 h-5" />
                        Configuration
                    </h2>

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
                                Statut
                            </label>
                            <select v-model="form.status" class="input">
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                                <option value="offline">Hors ligne</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                ID de l'appareil *
                            </label>
                            <input v-model="form.device_id" type="text" class="input" required />
                            <p v-if="form.errors.device_id" class="mt-1 text-sm text-red-600">{{ form.errors.device_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nom de la serrure
                            </label>
                            <input v-model="form.device_name" type="text" class="input" />
                            <p v-if="form.errors.device_name" class="mt-1 text-sm text-red-600">{{ form.errors.device_name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('tenant.iot.locks.index')" class="btn-secondary">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Supprimer la serrure ?</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Cette action est irreversible. Les journaux d'acces seront conserves.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false" class="btn-secondary">Annuler</button>
                    <button @click="deleteLock" class="btn-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium; }
.btn-secondary { @apply px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 font-medium; }
.btn-success { @apply px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium; }
.btn-danger { @apply px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium; }
</style>
