<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    ClockIcon,
    CheckCircleIcon,
    BellIcon,
    XCircleIcon,
    MapPinIcon,
    CubeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    entry: Object,
    position: Number,
    estimatedWait: String,
})

const getStatusInfo = (status) => {
    const info = {
        active: {
            icon: ClockIcon,
            color: 'blue',
            title: 'En attente',
            description: 'Vous êtes inscrit sur notre liste d\'attente. Nous vous contacterons dès qu\'un box correspondant à vos critères sera disponible.'
        },
        notified: {
            icon: BellIcon,
            color: 'yellow',
            title: 'Notifié',
            description: 'Nous vous avons notifié d\'une disponibilité ! Veuillez vérifier vos emails.'
        },
        converted: {
            icon: CheckCircleIcon,
            color: 'green',
            title: 'Converti',
            description: 'Félicitations ! Votre inscription a été convertie en contrat de location.'
        },
        expired: {
            icon: XCircleIcon,
            color: 'gray',
            title: 'Expiré',
            description: 'Votre inscription a expiré. Vous pouvez vous réinscrire si vous le souhaitez.'
        },
        cancelled: {
            icon: XCircleIcon,
            color: 'red',
            title: 'Annulé',
            description: 'Votre inscription a été annulée.'
        }
    }
    return info[status] || info.active
}

const statusInfo = getStatusInfo(props.entry?.status)

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
}
</script>

<template>
    <Head title="Statut Liste d'attente" />

    <PublicLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <!-- Status Header -->
                    <div :class="[
                        'p-8 text-center',
                        statusInfo.color === 'blue' ? 'bg-blue-50 dark:bg-blue-900/20' : '',
                        statusInfo.color === 'yellow' ? 'bg-yellow-50 dark:bg-yellow-900/20' : '',
                        statusInfo.color === 'green' ? 'bg-green-50 dark:bg-green-900/20' : '',
                        statusInfo.color === 'gray' ? 'bg-gray-50 dark:bg-gray-700/20' : '',
                        statusInfo.color === 'red' ? 'bg-red-50 dark:bg-red-900/20' : '',
                    ]">
                        <div :class="[
                            'w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4',
                            statusInfo.color === 'blue' ? 'bg-blue-100 dark:bg-blue-900' : '',
                            statusInfo.color === 'yellow' ? 'bg-yellow-100 dark:bg-yellow-900' : '',
                            statusInfo.color === 'green' ? 'bg-green-100 dark:bg-green-900' : '',
                            statusInfo.color === 'gray' ? 'bg-gray-100 dark:bg-gray-700' : '',
                            statusInfo.color === 'red' ? 'bg-red-100 dark:bg-red-900' : '',
                        ]">
                            <component
                                :is="statusInfo.icon"
                                :class="[
                                    'w-8 h-8',
                                    statusInfo.color === 'blue' ? 'text-blue-600 dark:text-blue-400' : '',
                                    statusInfo.color === 'yellow' ? 'text-yellow-600 dark:text-yellow-400' : '',
                                    statusInfo.color === 'green' ? 'text-green-600 dark:text-green-400' : '',
                                    statusInfo.color === 'gray' ? 'text-gray-600 dark:text-gray-400' : '',
                                    statusInfo.color === 'red' ? 'text-red-600 dark:text-red-400' : '',
                                ]"
                            />
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ statusInfo.title }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ statusInfo.description }}
                        </p>
                    </div>

                    <!-- Position (if active) -->
                    <div v-if="entry.status === 'active' && position" class="border-b border-gray-200 dark:border-gray-700 p-6">
                        <div class="text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Votre position</p>
                            <div class="text-5xl font-bold text-indigo-600 dark:text-indigo-400">
                                #{{ position }}
                            </div>
                            <p v-if="estimatedWait" class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                Temps d'attente estimé: {{ estimatedWait }}
                            </p>
                        </div>
                    </div>

                    <!-- Entry Details -->
                    <div class="p-6 space-y-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                Vos informations
                            </h3>
                            <div class="space-y-2">
                                <p class="text-gray-900 dark:text-white">
                                    {{ entry.customer_first_name }} {{ entry.customer_last_name }}
                                </p>
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ entry.customer_email }}
                                </p>
                            </div>
                        </div>

                        <div v-if="entry.site">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                Site souhaité
                            </h3>
                            <div class="flex items-center space-x-3">
                                <MapPinIcon class="w-5 h-5 text-gray-400" />
                                <span class="text-gray-900 dark:text-white">{{ entry.site.name }}</span>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                Critères de recherche
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-if="entry.min_size || entry.max_size" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Taille</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ entry.min_size || 0 }} - {{ entry.max_size || '∞' }} m²
                                    </p>
                                </div>
                                <div v-if="entry.max_price" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Budget max</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ entry.max_price }}€/mois
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                Dates
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Inscrit le</span>
                                    <span class="text-gray-900 dark:text-white">{{ formatDate(entry.created_at) }}</span>
                                </div>
                                <div v-if="entry.expires_at" class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Expire le</span>
                                    <span class="text-gray-900 dark:text-white">{{ formatDate(entry.expires_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <Link
                                v-if="entry.status === 'active'"
                                :href="route('public.waitlist.cancel', entry.token)"
                                method="post"
                                as="button"
                                class="flex-1 text-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            >
                                Se désinscrire
                            </Link>
                            <Link
                                v-if="['expired', 'cancelled'].includes(entry.status)"
                                :href="route('public.waitlist.register', { site: entry.site_id })"
                                class="flex-1 text-center py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                            >
                                Se réinscrire
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    <p>
                        Des questions ? Contactez-nous à
                        <a href="mailto:contact@example.com" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                            contact@example.com
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
