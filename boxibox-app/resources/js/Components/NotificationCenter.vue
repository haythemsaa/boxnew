<template>
    <div class="relative" v-click-outside="closeDropdown">
        <!-- Notification Bell Button -->
        <button
            @click="toggleNotifications"
            class="relative p-2 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all duration-200"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <!-- Badge compteur -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
            <!-- Point critique -->
            <span
                v-if="criticalCount > 0"
                class="absolute top-0 right-0 w-2 h-2 bg-red-600 rounded-full ring-2 ring-white dark:ring-gray-800"
            ></span>
        </button>

        <!-- Dropdown Panel -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="showNotifications"
                class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
            >
                <!-- Header -->
                <div class="px-4 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-lg">Notifications</h3>
                            <p class="text-primary-100 text-sm">{{ unreadCount }} non lues</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                v-if="unreadCount > 0"
                                @click="markAllAsRead"
                                class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded-lg transition-colors"
                            >
                                Tout marquer lu
                            </button>
                            <button
                                @click="refreshNotifications"
                                :class="['p-1 rounded-lg transition-colors', isLoading ? 'animate-spin' : 'hover:bg-white/20']"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex space-x-2">
                    <button
                        v-for="filter in filters"
                        :key="filter.key"
                        @click="activeFilter = filter.key"
                        :class="[
                            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
                            activeFilter === filter.key
                                ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
                        ]"
                    >
                        {{ filter.label }}
                        <span v-if="filter.count > 0" class="ml-1 text-xs">{{ filter.count }}</span>
                    </button>
                </div>

                <!-- Liste des notifications -->
                <div class="max-h-[400px] overflow-y-auto">
                    <!-- Loading -->
                    <div v-if="isLoading" class="p-8 text-center">
                        <div class="animate-spin w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-500">Chargement...</p>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="filteredNotifications.length === 0" class="p-8 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">Aucune notification</p>
                    </div>

                    <!-- Notifications list -->
                    <div v-else>
                        <div
                            v-for="notification in filteredNotifications"
                            :key="notification.id"
                            @click="handleNotificationClick(notification)"
                            :class="[
                                'px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors',
                                !notification.is_read ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Icon -->
                                <div :class="getIconClass(notification)">
                                    <component :is="getIcon(notification)" class="w-5 h-5" />
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p :class="[
                                            'text-sm font-medium truncate',
                                            !notification.is_read ? 'text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300'
                                        ]">
                                            {{ notification.title }}
                                        </p>
                                        <span
                                            v-if="notification.data?.priority === 'critical'"
                                            class="flex-shrink-0 px-1.5 py-0.5 text-xs font-bold bg-red-100 text-red-700 rounded"
                                        >
                                            URGENT
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                                        {{ notification.message }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-gray-400">{{ timeAgo(notification.created_at) }}</span>
                                        <span
                                            v-if="notification.data?.amount"
                                            class="text-xs font-medium text-gray-600 dark:text-gray-300"
                                        >
                                            {{ formatCurrency(notification.data.amount) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Unread indicator -->
                                <div v-if="!notification.is_read" class="flex-shrink-0 mt-1">
                                    <span class="w-2 h-2 bg-primary-500 rounded-full block"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                    <a
                        :href="route('tenant.notifications.index')"
                        class="block text-center text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
                    >
                        Voir toutes les notifications →
                    </a>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'

// Icons as render functions
const ExclamationTriangleIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' })
])
const ClockIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' })
])
const CalendarIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
])
const CheckCircleIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
])
const DocumentTextIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
])
const UserPlusIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' })
])
const ChartBarIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
])
const BellIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' })
])

const props = defineProps({
    initialNotifications: { type: Array, default: () => [] },
    initialUnreadCount: { type: Number, default: 0 },
})

const showNotifications = ref(false)
const notifications = ref([])
const isLoading = ref(false)
const activeFilter = ref('all')
const pollingInterval = ref(null)

// Récupérer le count depuis les props de la page
const page = usePage()
const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.is_read).length || page.props.notificationsCount || 0
})

const criticalCount = computed(() => {
    return notifications.value.filter(n => !n.is_read && n.data?.color === 'red').length
})

const filters = computed(() => [
    { key: 'all', label: 'Tout', count: notifications.value.length },
    { key: 'unread', label: 'Non lues', count: notifications.value.filter(n => !n.is_read).length },
    { key: 'payment', label: 'Paiements', count: notifications.value.filter(n => n.type === 'payment_reminder' || n.type === 'payment_received').length },
    { key: 'contract', label: 'Contrats', count: notifications.value.filter(n => n.type === 'contract_expiring').length },
])

const filteredNotifications = computed(() => {
    let result = notifications.value

    switch (activeFilter.value) {
        case 'unread':
            result = result.filter(n => !n.is_read)
            break
        case 'payment':
            result = result.filter(n => n.type === 'payment_reminder' || n.type === 'payment_received')
            break
        case 'contract':
            result = result.filter(n => n.type === 'contract_expiring')
            break
    }

    return result.slice(0, 20) // Limiter à 20
})

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
    if (showNotifications.value && notifications.value.length === 0) {
        fetchNotifications()
    }
}

const closeDropdown = () => {
    showNotifications.value = false
}

const fetchNotifications = async () => {
    isLoading.value = true
    try {
        const response = await axios.get(route('tenant.notifications.api.list'))
        notifications.value = response.data.notifications || []
    } catch (error) {
        console.error('Erreur chargement notifications:', error)
        notifications.value = []
    } finally {
        isLoading.value = false
    }
}

const refreshNotifications = () => {
    fetchNotifications()
}

const markAsRead = async (id) => {
    try {
        await axios.post(route('tenant.notifications.api.read', id))
        const notification = notifications.value.find(n => n.id === id)
        if (notification) {
            notification.is_read = true
        }
    } catch (error) {
        console.error('Erreur marquage notification:', error)
    }
}

const markAllAsRead = async () => {
    try {
        await axios.post(route('tenant.notifications.api.read-all'))
        notifications.value.forEach(n => n.is_read = true)
    } catch (error) {
        console.error('Erreur marquage toutes notifications:', error)
    }
}

const handleNotificationClick = (notification) => {
    markAsRead(notification.id)

    // Navigation basée sur le type
    if (notification.related_type && notification.related_id) {
        const routes = {
            'invoice': 'tenant.invoices.show',
            'contract': 'tenant.contracts.show',
            'customer': 'tenant.customers.show',
            'payment': 'tenant.payments.show',
        }

        const routeName = routes[notification.related_type]
        if (routeName) {
            router.visit(route(routeName, notification.related_id))
            showNotifications.value = false
        }
    }
}

const getIcon = (notification) => {
    const iconMap = {
        'payment_reminder': ExclamationTriangleIcon,
        'payment_received': CheckCircleIcon,
        'contract_expiring': CalendarIcon,
        'invoice_generated': DocumentTextIcon,
        'message_received': BellIcon,
    }

    // Si c'est une alerte sur le taux d'occupation
    if (notification.data?.icon === 'chart-bar') {
        return ChartBarIcon
    }

    // Si c'est un nouveau client
    if (notification.data?.icon === 'user-plus') {
        return UserPlusIcon
    }

    return iconMap[notification.type] || BellIcon
}

const getIconClass = (notification) => {
    const colorMap = {
        'red': 'text-red-600 bg-red-100 dark:bg-red-900/30',
        'orange': 'text-orange-600 bg-orange-100 dark:bg-orange-900/30',
        'yellow': 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900/30',
        'green': 'text-green-600 bg-green-100 dark:bg-green-900/30',
        'blue': 'text-blue-600 bg-blue-100 dark:bg-blue-900/30',
    }

    const color = notification.data?.color || 'blue'
    return `flex-shrink-0 p-2 rounded-full ${colorMap[color] || colorMap.blue}`
}

const timeAgo = (date) => {
    if (!date) return ''

    const seconds = Math.floor((new Date() - new Date(date)) / 1000)
    const intervals = {
        'an': 31536000,
        'mois': 2592000,
        'semaine': 604800,
        'jour': 86400,
        'heure': 3600,
        'minute': 60,
    }

    for (const [key, value] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / value)
        if (interval >= 1) {
            const plural = interval > 1 && key !== 'mois' ? 's' : ''
            return `il y a ${interval} ${key}${plural}`
        }
    }

    return 'à l\'instant'
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount)
}

// Custom directive for click outside
const vClickOutside = {
    mounted(el, binding) {
        el._clickOutside = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value()
            }
        }
        document.addEventListener('click', el._clickOutside)
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutside)
    }
}

// Polling pour rafraîchir les notifications toutes les 60 secondes
onMounted(() => {
    fetchNotifications()
    pollingInterval.value = setInterval(fetchNotifications, 60000)
})

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value)
    }
})
</script>
