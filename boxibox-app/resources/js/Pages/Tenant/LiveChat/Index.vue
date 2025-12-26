<template>
    <TenantLayout title="Chat en direct">
        <div class="max-w-7xl mx-auto">
            <!-- Header with Stats -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Chat en direct</h1>
                        <p class="text-gray-600 mt-1">Gerez les conversations avec vos clients en temps reel</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium"
                            :class="isOnline ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="isOnline ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"
                            ></span>
                            {{ isOnline ? 'En ligne' : 'Hors ligne' }}
                        </span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Total</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                            </div>
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                                <ChatBubbleLeftRightIcon class="w-6 h-6 text-gray-500" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border border-green-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Actives</p>
                                <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.active }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <ChatBubbleOvalLeftEllipsisIcon class="w-6 h-6 text-green-600" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border border-red-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Non lus</p>
                                <p class="text-2xl font-bold text-red-600 mt-1">{{ stats.unread }}</p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <BellAlertIcon class="w-6 h-6 text-red-600" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-5 border border-blue-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Fermees aujourd'hui</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.closed_today }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-blue-600" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="flex gap-2">
                        <button
                            v-for="status in statusFilters"
                            :key="status.value"
                            @click="applyFilter(status.value)"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                filters.status === status.value
                                    ? 'bg-primary-100 text-primary-700 border border-primary-200'
                                    : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-transparent'
                            ]"
                        >
                            {{ status.label }}
                            <span
                                v-if="status.count"
                                class="ml-2 px-2 py-0.5 text-xs rounded-full"
                                :class="filters.status === status.value ? 'bg-primary-200' : 'bg-gray-200'"
                            >
                                {{ status.count }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conversations List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div v-if="conversations.data?.length === 0" class="p-12 text-center">
                    <ChatBubbleLeftRightIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune conversation</h3>
                    <p class="text-gray-500">Les conversations avec vos clients apparaitront ici</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <Link
                        v-for="conversation in conversations.data"
                        :key="conversation.id"
                        :href="route('tenant.live-chat.show', conversation.id)"
                        class="flex items-center p-4 hover:bg-gray-50 transition group"
                    >
                        <!-- Customer Avatar -->
                        <div class="relative flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-700 rounded-full flex items-center justify-center text-white font-bold">
                                {{ getInitials(conversation.customer) }}
                            </div>
                            <span
                                class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white"
                                :class="conversation.status === 'active' ? 'bg-green-500' : 'bg-gray-300'"
                            ></span>
                        </div>

                        <!-- Conversation Info -->
                        <div class="ml-4 flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-gray-900 truncate">
                                    {{ conversation.customer?.first_name }} {{ conversation.customer?.last_name }}
                                </h4>
                                <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                                    {{ formatTime(conversation.last_message_at) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 truncate mt-0.5">
                                {{ conversation.customer?.email }}
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="getStatusClass(conversation.status)"
                                >
                                    {{ getStatusLabel(conversation.status) }}
                                </span>
                                <span class="text-xs text-gray-400">
                                    via {{ conversation.channel }}
                                </span>
                            </div>
                        </div>

                        <!-- Unread Badge -->
                        <div v-if="conversation.unread_count > 0" class="ml-4 flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-red-500 text-white text-xs font-bold rounded-full">
                                {{ conversation.unread_count }}
                            </span>
                        </div>

                        <ChevronRightIcon class="w-5 h-5 text-gray-400 ml-2 group-hover:text-gray-600 transition" />
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="conversations.last_page > 1" class="border-t border-gray-100 px-4 py-3 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Affichage {{ conversations.from }} - {{ conversations.to }} sur {{ conversations.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="conversations.prev_page_url"
                            :href="conversations.prev_page_url"
                            class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Precedent
                        </Link>
                        <Link
                            v-if="conversations.next_page_url"
                            :href="conversations.next_page_url"
                            class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Suivant
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ChatBubbleLeftRightIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    BellAlertIcon,
    CheckCircleIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    conversations: Object,
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            unread: 0,
            closed_today: 0
        })
    },
    filters: {
        type: Object,
        default: () => ({ status: null })
    },
})

const isOnline = ref(true)
let echoChannel = null

const statusFilters = computed(() => [
    { value: null, label: 'Toutes', count: props.stats.total },
    { value: 'active', label: 'Actives', count: props.stats.active },
    { value: 'closed', label: 'Fermees', count: props.stats.total - props.stats.active },
])

const getInitials = (customer) => {
    if (!customer) return '?'
    const first = customer.first_name?.[0] || ''
    const last = customer.last_name?.[0] || ''
    return (first + last).toUpperCase() || '?'
}

const getStatusClass = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-700'
        case 'closed':
            return 'bg-gray-100 text-gray-600'
        case 'handed_off':
            return 'bg-yellow-100 text-yellow-700'
        default:
            return 'bg-gray-100 text-gray-600'
    }
}

const getStatusLabel = (status) => {
    switch (status) {
        case 'active':
            return 'Active'
        case 'closed':
            return 'Fermee'
        case 'handed_off':
            return 'Transferee'
        default:
            return status
    }
}

const formatTime = (date) => {
    if (!date) return ''
    const d = new Date(date)
    const now = new Date()
    const diff = now - d

    // Less than 1 minute
    if (diff < 60000) return 'A l\'instant'

    // Less than 1 hour
    if (diff < 3600000) {
        const mins = Math.floor(diff / 60000)
        return `Il y a ${mins} min`
    }

    // Less than 24 hours
    if (diff < 86400000) {
        return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    }

    // Less than 7 days
    if (diff < 604800000) {
        return d.toLocaleDateString('fr-FR', { weekday: 'short', hour: '2-digit', minute: '2-digit' })
    }

    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const applyFilter = (status) => {
    router.get(route('tenant.live-chat.index'), { status }, { preserveState: true })
}

// Setup Echo for real-time updates
const setupEcho = () => {
    if (!window.Echo) return

    // Listen for new conversations
    const tenantId = document.querySelector('meta[name="tenant-id"]')?.content

    if (tenantId) {
        echoChannel = window.Echo.private(`tenant.${tenantId}.chats`)
            .listen('.conversation.new', (data) => {
                // Reload to show new conversation
                router.reload({ only: ['conversations', 'stats'] })
            })
            .listen('.message.sent', (data) => {
                // Reload to update unread counts
                router.reload({ only: ['conversations', 'stats'] })
            })
    }
}

onMounted(() => {
    setupEcho()
})

onUnmounted(() => {
    if (echoChannel) {
        const tenantId = document.querySelector('meta[name="tenant-id"]')?.content
        if (tenantId) {
            window.Echo.leave(`tenant.${tenantId}.chats`)
        }
    }
})
</script>
