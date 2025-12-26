<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    MapPinIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    BellIcon,
    ArrowLeftIcon,
    TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    entry: Object,
    notifications: Array,
    matchingBoxes: Array,
})

const notifying = ref(false)
const converting = ref(false)

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        notified: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        converted: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        expired: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }
    return classes[status] || classes.active
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const sendNotification = () => {
    if (confirm('Envoyer une notification au client pour l\'informer de la disponibilité ?')) {
        notifying.value = true
        router.post(route('tenant.waitlist.notify', props.entry.id), {}, {
            onFinish: () => notifying.value = false
        })
    }
}

const convertToContract = (boxId) => {
    if (confirm('Convertir cette entrée en contrat de location ?')) {
        converting.value = true
        router.post(route('tenant.waitlist.convert', props.entry.id), { box_id: boxId }, {
            onFinish: () => converting.value = false
        })
    }
}

const cancelEntry = () => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette entrée ?')) {
        router.post(route('tenant.waitlist.cancel', props.entry.id))
    }
}

const deleteEntry = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement cette entrée ?')) {
        router.delete(route('tenant.waitlist.destroy', props.entry.id))
    }
}
</script>

<template>
    <Head :title="`Liste d'attente - ${entry.customer_first_name} ${entry.customer_last_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('tenant.waitlist.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ entry.customer_first_name }} {{ entry.customer_last_name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Position #{{ entry.position }} - {{ entry.site?.name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span :class="['px-3 py-1 text-sm font-medium rounded-full', getStatusBadgeClass(entry.status)]">
                        {{ entry.status_label }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Customer Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Informations client
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center space-x-3">
                                    <UserIcon class="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Nom complet</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ entry.customer_first_name }} {{ entry.customer_last_name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <EnvelopeIcon class="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                        <a :href="`mailto:${entry.customer_email}`" class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ entry.customer_email }}
                                        </a>
                                    </div>
                                </div>
                                <div v-if="entry.customer_phone" class="flex items-center space-x-3">
                                    <PhoneIcon class="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Téléphone</p>
                                        <a :href="`tel:${entry.customer_phone}`" class="font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ entry.customer_phone }}
                                        </a>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <MapPinIcon class="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Site souhaité</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ entry.site?.name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preferences -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Critères de recherche
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Taille souhaitée</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ entry.min_size || 0 }} - {{ entry.max_size || '∞' }} m²
                                    </p>
                                </div>
                                <div v-if="entry.max_price" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Budget max</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ entry.max_price }}€/mois
                                    </p>
                                </div>
                                <div v-if="entry.box" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Box spécifique</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                        Box {{ entry.box?.name }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="entry.notes" class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
                                <p class="text-gray-700 dark:text-gray-300">{{ entry.notes }}</p>
                            </div>
                        </div>

                        <!-- Matching Boxes -->
                        <div v-if="matchingBoxes && matchingBoxes.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Box disponibles correspondants
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="box in matchingBoxes"
                                    :key="box.id"
                                    class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 rounded-lg"
                                >
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            Box {{ box.name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ box.size }} m² - {{ box.price }}€/mois
                                        </p>
                                    </div>
                                    <button
                                        v-if="entry.status === 'active' || entry.status === 'notified'"
                                        @click="convertToContract(box.id)"
                                        :disabled="converting"
                                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
                                    >
                                        <CheckCircleIcon class="w-4 h-4 inline mr-1" />
                                        Convertir
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications History -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Historique des notifications
                            </h3>
                            <div v-if="notifications && notifications.length > 0" class="space-y-3">
                                <div
                                    v-for="notification in notifications"
                                    :key="notification.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                >
                                    <div class="flex items-center space-x-3">
                                        <BellIcon class="w-5 h-5 text-gray-400" />
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ notification.type_label }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDate(notification.sent_at) }}
                                            </p>
                                        </div>
                                    </div>
                                    <span :class="[
                                        'px-2 py-1 text-xs rounded-full',
                                        notification.read_at
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300'
                                    ]">
                                        {{ notification.read_at ? 'Lu' : 'Non lu' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <BellIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                                <p class="text-gray-500 dark:text-gray-400">Aucune notification envoyée</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Timeline -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Chronologie
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <ClockIcon class="w-5 h-5 text-gray-400" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Inscription</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(entry.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="entry.notified_at" class="flex items-center space-x-3">
                                    <BellIcon class="w-5 h-5 text-yellow-500" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Dernière notification</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(entry.notified_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="entry.converted_at" class="flex items-center space-x-3">
                                    <CheckCircleIcon class="w-5 h-5 text-green-500" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Converti</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(entry.converted_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="entry.expires_at" class="flex items-center space-x-3">
                                    <XCircleIcon class="w-5 h-5 text-red-500" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Expiration</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(entry.expires_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Actions
                            </h3>
                            <div class="space-y-3">
                                <button
                                    v-if="entry.status === 'active'"
                                    @click="sendNotification"
                                    :disabled="notifying"
                                    class="w-full flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <BellIcon class="w-4 h-4 mr-2" />
                                    {{ notifying ? 'Envoi...' : 'Notifier le client' }}
                                </button>

                                <button
                                    v-if="entry.status === 'active' || entry.status === 'notified'"
                                    @click="cancelEntry"
                                    class="w-full flex items-center justify-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700"
                                >
                                    <XCircleIcon class="w-4 h-4 mr-2" />
                                    Annuler l'entrée
                                </button>

                                <button
                                    @click="deleteEntry"
                                    class="w-full flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                >
                                    <TrashIcon class="w-4 h-4 mr-2" />
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
