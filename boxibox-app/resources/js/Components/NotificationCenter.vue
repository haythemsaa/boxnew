<template>
    <div class="space-y-3">
        <!-- Notification Bell -->
        <div class="relative">
            <button
                @click="toggleNotifications"
                class="relative p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <span v-if="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                    {{ unreadCount }}
                </span>
            </button>

            <!-- Dropdown Menu -->
            <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-900">Notifications</h3>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                        No notifications yet
                    </div>

                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="markAsRead(notification.id)"
                        :class="[
                            'p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors',
                            !notification.read ? 'bg-blue-50' : ''
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <div :class="getIconClass(notification.type)" class="flex-shrink-0">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path v-if="notification.type === 'invoice'" fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                    <path v-else-if="notification.type === 'contract'" fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1V3a1 1 0 011-1h6a1 1 0 011 1v1h1V3a1 1 0 011 1v1h2a2 2 0 012 2v2h1a1 1 0 110 2h-1v6h1a1 1 0 110 2h-1v2a2 2 0 01-2 2h-2v1a1 1 0 11-2 0v-1h-6v1a1 1 0 11-2 0v-1H7a2 2 0 01-2-2v-2H4a1 1 0 110-2h1V9H4a1 1 0 110-2h1V5a2 2 0 012-2zm4 4a2 2 0 100 4 2 2 0 000-4z" clip-rule="evenodd"></path>
                                    <path v-else fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ notification.message }}</p>
                                <p class="text-xs text-gray-500 mt-2">{{ timeAgo(notification.created_at) }}</p>
                            </div>
                            <div v-if="!notification.read" class="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full"></div>
                        </div>
                    </div>
                </div>

                <div class="p-3 border-t border-gray-200 text-center">
                    <a href="/admin/notifications" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        View all notifications →
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const showNotifications = ref(false)
const notifications = ref([])

// Mock notifications data - in production, fetch from API
const mockNotifications = [
    {
        id: 1,
        type: 'invoice',
        title: 'Invoice INV-2025-12-001 Due',
        message: 'Payment due in 3 days',
        created_at: new Date(Date.now() - 3600000),
        read: false,
    },
    {
        id: 2,
        type: 'contract',
        title: 'Contract CNT-001 Expires Soon',
        message: 'Your storage contract expires in 30 days',
        created_at: new Date(Date.now() - 86400000),
        read: false,
    },
    {
        id: 3,
        type: 'payment',
        title: 'Payment Received',
        message: 'Your payment of €500 has been received',
        created_at: new Date(Date.now() - 172800000),
        read: true,
    },
]

const unreadCount = computed(() => {
    return notifications.value.filter(n => !n.read).length
})

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
}

const markAsRead = (id) => {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
        notification.read = true
    }
}

const getIconClass = (type) => {
    const classes = {
        invoice: 'text-blue-600 bg-blue-100 rounded-full p-2',
        contract: 'text-green-600 bg-green-100 rounded-full p-2',
        payment: 'text-purple-600 bg-purple-100 rounded-full p-2',
    }
    return classes[type] || 'text-gray-600 bg-gray-100 rounded-full p-2'
}

const timeAgo = (date) => {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000)
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60,
    }

    for (const [key, value] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / value)
        if (interval >= 1) {
            return interval === 1 ? `1 ${key} ago` : `${interval} ${key}s ago`
        }
    }

    return 'just now'
}

onMounted(() => {
    notifications.value = mockNotifications
})
</script>
