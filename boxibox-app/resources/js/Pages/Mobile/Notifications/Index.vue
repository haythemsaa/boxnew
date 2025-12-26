<template>
    <MobileLayout title="Notifications" :show-back="true">
        <!-- Header Actions -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Toutes les notifications</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ unreadCount }} non lue(s)</p>
            </div>
            <button
                v-if="unreadCount > 0"
                @click="markAllAsRead"
                class="px-4 py-2 text-sm font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded-xl active:scale-95 transition"
            >
                Tout marquer lu
            </button>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 mb-6 overflow-x-auto pb-2 -mx-4 px-4">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="activeFilter = filter.value"
                class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition"
                :class="activeFilter === filter.value
                    ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/30'
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-sm'"
            >
                {{ filter.label }}
                <span
                    v-if="filter.count > 0"
                    class="ml-1 px-1.5 py-0.5 text-xs rounded-full"
                    :class="activeFilter === filter.value
                        ? 'bg-white/20 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                >
                    {{ filter.count }}
                </span>
            </button>
        </div>

        <!-- Notifications List -->
        <div v-if="filteredNotifications.length > 0" class="space-y-3">
            <TransitionGroup
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 translate-x-4"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0 -translate-x-full"
            >
                <div
                    v-for="notification in filteredNotifications"
                    :key="notification.id"
                    class="rounded-2xl p-4 transition-all duration-200 active:scale-[0.98]"
                    :class="notification.read
                        ? 'bg-white dark:bg-gray-800 shadow-sm'
                        : 'bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/30 dark:to-blue-900/30 border-l-4 border-primary-500 shadow-md'"
                    @click="openNotification(notification)"
                >
                    <div class="flex items-start">
                        <!-- Icon -->
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                            :class="getIconBgClass(notification.type, notification.read)"
                        >
                            <component
                                :is="getNotificationIcon(notification.type)"
                                class="w-6 h-6"
                                :class="getIconClass(notification.type, notification.read)"
                            />
                        </div>

                        <!-- Content -->
                        <div class="ml-4 flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ notification.title }}
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ notification.message }}
                                    </p>
                                </div>
                                <button
                                    @click.stop="toggleRead(notification)"
                                    class="ml-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    <EyeIcon v-if="!notification.read" class="w-5 h-5 text-gray-400" />
                                    <EyeSlashIcon v-else class="w-5 h-5 text-gray-400" />
                                </button>
                            </div>

                            <!-- Meta -->
                            <div class="flex items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <ClockIcon class="w-3.5 h-3.5 mr-1" />
                                <span>{{ formatTime(notification.created_at) }}</span>
                                <span class="mx-2">•</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="getTypeClass(notification.type)"
                                >
                                    {{ getTypeLabel(notification.type) }}
                                </span>
                            </div>

                            <!-- Action Button -->
                            <button
                                v-if="notification.action_url"
                                @click.stop="goToAction(notification)"
                                class="mt-3 inline-flex items-center px-3 py-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded-lg active:scale-95 transition"
                            >
                                {{ notification.action_label || 'Voir plus' }}
                                <ArrowRightIcon class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>
            </TransitionGroup>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-16">
            <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <BellSlashIcon class="w-12 h-12 text-gray-300 dark:text-gray-600" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aucune notification</h3>
            <p class="text-gray-500 dark:text-gray-400">
                {{ activeFilter === 'all' ? 'Vous n\'avez pas de notification' : 'Aucune notification dans cette categorie' }}
            </p>
        </div>

        <!-- Load More -->
        <div v-if="hasMore" class="mt-6 text-center">
            <button
                @click="loadMore"
                :disabled="loadingMore"
                class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl shadow-sm active:scale-95 transition disabled:opacity-50"
            >
                <span v-if="loadingMore" class="flex items-center justify-center">
                    <ArrowPathIcon class="w-5 h-5 mr-2 animate-spin" />
                    Chargement...
                </span>
                <span v-else>Charger plus</span>
            </button>
        </div>

        <!-- Notification Detail Modal -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="selectedNotification" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4">
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-y-full"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-full"
                >
                    <div
                        v-if="selectedNotification"
                        class="bg-white dark:bg-gray-800 rounded-t-3xl w-full max-w-lg shadow-xl"
                    >
                        <!-- Handle -->
                        <div class="flex justify-center pt-3">
                            <div class="w-12 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                        </div>

                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex items-start mb-4">
                                <div
                                    class="w-14 h-14 rounded-xl flex items-center justify-center"
                                    :class="getIconBgClass(selectedNotification.type, false)"
                                >
                                    <component
                                        :is="getNotificationIcon(selectedNotification.type)"
                                        class="w-7 h-7"
                                        :class="getIconClass(selectedNotification.type, false)"
                                    />
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ selectedNotification.title }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatTime(selectedNotification.created_at) }}
                                    </p>
                                </div>
                                <button
                                    @click="selectedNotification = null"
                                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                >
                                    <XMarkIcon class="w-5 h-5 text-gray-500" />
                                </button>
                            </div>

                            <!-- Message -->
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                                {{ selectedNotification.message }}
                            </p>

                            <!-- Actions -->
                            <div class="flex gap-3">
                                <button
                                    @click="selectedNotification = null"
                                    class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl active:scale-95 transition"
                                >
                                    Fermer
                                </button>
                                <button
                                    v-if="selectedNotification.action_url"
                                    @click="goToAction(selectedNotification)"
                                    class="flex-1 py-3 bg-primary-600 text-white font-semibold rounded-xl active:scale-95 transition"
                                >
                                    {{ selectedNotification.action_label || 'Voir plus' }}
                                </button>
                            </div>
                        </div>

                        <!-- Safe area -->
                        <div class="h-safe-area-inset-bottom bg-white dark:bg-gray-800"></div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    BellIcon,
    BellSlashIcon,
    ClockIcon,
    DocumentTextIcon,
    CreditCardIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    InformationCircleIcon,
    CubeIcon,
    KeyIcon,
    EyeIcon,
    EyeSlashIcon,
    XMarkIcon,
    ArrowRightIcon,
    ArrowPathIcon,
    GiftIcon,
    MegaphoneIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    notifications: Array,
})

const activeFilter = ref('all')
const selectedNotification = ref(null)
const loadingMore = ref(false)
const hasMore = ref(true)

// Demo notifications
const notifications = ref(props.notifications || [
    {
        id: 1,
        type: 'invoice',
        title: 'Nouvelle facture disponible',
        message: 'Votre facture de decembre 2024 est maintenant disponible. Montant: 149,99€. Date d\'echeance: 15 janvier 2025.',
        created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
        read: false,
        action_url: '/mobile/invoices',
        action_label: 'Voir la facture'
    },
    {
        id: 2,
        type: 'payment',
        title: 'Paiement confirme',
        message: 'Votre paiement de 149,99€ par carte bancaire a ete accepte. Merci pour votre confiance.',
        created_at: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString(),
        read: false,
        action_url: '/mobile/payments',
        action_label: 'Voir le recu'
    },
    {
        id: 3,
        type: 'alert',
        title: 'Rappel: Contrat expire bientot',
        message: 'Votre contrat pour le Box A-12 expire dans 30 jours. Pensez a le renouveler pour eviter toute interruption de service.',
        created_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
        read: true,
        action_url: '/mobile/contracts',
        action_label: 'Renouveler'
    },
    {
        id: 4,
        type: 'access',
        title: 'Nouvel acces enregistre',
        message: 'Un acces a ete enregistre sur votre box A-12 aujourd\'hui a 14h32.',
        created_at: new Date(Date.now() - 5 * 60 * 60 * 1000).toISOString(),
        read: true,
    },
    {
        id: 5,
        type: 'promo',
        title: 'Offre speciale: -20% sur le parrainage',
        message: 'Parrainez un ami et beneficiez tous les deux de 20% de reduction sur votre prochain mois!',
        created_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString(),
        read: true,
        action_url: '/mobile/referral',
        action_label: 'Parrainer'
    },
    {
        id: 6,
        type: 'info',
        title: 'Maintenance prevue',
        message: 'Une maintenance est prevue le 28 decembre de 2h a 4h du matin. L\'acces au site pourrait etre temporairement limite.',
        created_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
        read: true,
    },
])

const filters = computed(() => [
    { value: 'all', label: 'Toutes', count: notifications.value.length },
    { value: 'unread', label: 'Non lues', count: unreadCount.value },
    { value: 'invoice', label: 'Factures', count: notifications.value.filter(n => n.type === 'invoice').length },
    { value: 'payment', label: 'Paiements', count: notifications.value.filter(n => n.type === 'payment').length },
    { value: 'alert', label: 'Alertes', count: notifications.value.filter(n => n.type === 'alert').length },
])

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

const filteredNotifications = computed(() => {
    if (activeFilter.value === 'all') return notifications.value
    if (activeFilter.value === 'unread') return notifications.value.filter(n => !n.read)
    return notifications.value.filter(n => n.type === activeFilter.value)
})

const formatTime = (date) => {
    const now = new Date()
    const notifDate = new Date(date)
    const diff = now - notifDate
    const minutes = Math.floor(diff / (1000 * 60))
    const hours = Math.floor(diff / (1000 * 60 * 60))
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))

    if (minutes < 60) return `Il y a ${minutes} min`
    if (hours < 24) return `Il y a ${hours}h`
    if (days < 7) return `Il y a ${days} jour${days > 1 ? 's' : ''}`
    return notifDate.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const getNotificationIcon = (type) => {
    const icons = {
        invoice: DocumentTextIcon,
        payment: CreditCardIcon,
        alert: ExclamationTriangleIcon,
        success: CheckCircleIcon,
        info: InformationCircleIcon,
        access: KeyIcon,
        box: CubeIcon,
        promo: GiftIcon,
        announcement: MegaphoneIcon,
    }
    return icons[type] || BellIcon
}

const getIconBgClass = (type, read) => {
    if (read) return 'bg-gray-100 dark:bg-gray-700'

    const classes = {
        invoice: 'bg-blue-100 dark:bg-blue-900/50',
        payment: 'bg-green-100 dark:bg-green-900/50',
        alert: 'bg-amber-100 dark:bg-amber-900/50',
        success: 'bg-green-100 dark:bg-green-900/50',
        info: 'bg-blue-100 dark:bg-blue-900/50',
        access: 'bg-purple-100 dark:bg-purple-900/50',
        box: 'bg-indigo-100 dark:bg-indigo-900/50',
        promo: 'bg-pink-100 dark:bg-pink-900/50',
        announcement: 'bg-orange-100 dark:bg-orange-900/50',
    }
    return classes[type] || 'bg-gray-100 dark:bg-gray-700'
}

const getIconClass = (type, read) => {
    if (read) return 'text-gray-400 dark:text-gray-500'

    const classes = {
        invoice: 'text-blue-600 dark:text-blue-400',
        payment: 'text-green-600 dark:text-green-400',
        alert: 'text-amber-600 dark:text-amber-400',
        success: 'text-green-600 dark:text-green-400',
        info: 'text-blue-600 dark:text-blue-400',
        access: 'text-purple-600 dark:text-purple-400',
        box: 'text-indigo-600 dark:text-indigo-400',
        promo: 'text-pink-600 dark:text-pink-400',
        announcement: 'text-orange-600 dark:text-orange-400',
    }
    return classes[type] || 'text-gray-500'
}

const getTypeClass = (type) => {
    const classes = {
        invoice: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400',
        payment: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        alert: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-400',
        success: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        info: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400',
        access: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400',
        promo: 'bg-pink-100 text-pink-700 dark:bg-pink-900/50 dark:text-pink-400',
    }
    return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
}

const getTypeLabel = (type) => {
    const labels = {
        invoice: 'Facture',
        payment: 'Paiement',
        alert: 'Alerte',
        success: 'Succes',
        info: 'Info',
        access: 'Acces',
        box: 'Box',
        promo: 'Promo',
        announcement: 'Annonce',
    }
    return labels[type] || 'Notification'
}

const openNotification = (notification) => {
    if (!notification.read) {
        notification.read = true
    }
    selectedNotification.value = notification
}

const toggleRead = (notification) => {
    notification.read = !notification.read
}

const markAllAsRead = () => {
    notifications.value.forEach(n => n.read = true)
}

const goToAction = (notification) => {
    if (notification.action_url) {
        selectedNotification.value = null
        router.visit(notification.action_url)
    }
}

const loadMore = async () => {
    loadingMore.value = true
    // Simulate loading
    await new Promise(resolve => setTimeout(resolve, 1000))
    loadingMore.value = false
    hasMore.value = false
}
</script>

<style scoped>
.h-safe-area-inset-bottom {
    height: env(safe-area-inset-bottom, 0px);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
