<template>
    <TenantLayout :title="`Chat avec ${customer?.name || 'Client'}`">
        <div class="max-w-7xl mx-auto">
            <div class="flex gap-6 h-[calc(100vh-180px)]">
                <!-- Chat Window (Main) -->
                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Chat Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <div class="flex items-center gap-4">
                            <Link :href="route('tenant.live-chat.index')" class="p-2 hover:bg-gray-200 rounded-lg transition">
                                <ArrowLeftIcon class="w-5 h-5 text-gray-600" />
                            </Link>
                            <div class="relative">
                                <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-700 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ getInitials(customer) }}
                                </div>
                                <span
                                    class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white"
                                    :class="conversation.status === 'active' ? 'bg-green-500' : 'bg-gray-300'"
                                ></span>
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-900">{{ customer?.name }}</h2>
                                <p class="text-sm text-gray-500">{{ customer?.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="px-3 py-1 rounded-full text-sm font-medium"
                                :class="getStatusClass(conversation.status)"
                            >
                                {{ getStatusLabel(conversation.status) }}
                            </span>
                            <button
                                v-if="conversation.status === 'active'"
                                @click="closeConversation"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Fermer
                            </button>
                            <button
                                v-else
                                @click="reopenConversation"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
                            >
                                Rouvrir
                            </button>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50"
                    >
                        <!-- Date Separator -->
                        <div class="flex items-center justify-center">
                            <span class="px-4 py-1 bg-white rounded-full text-xs font-medium text-gray-500 shadow-sm">
                                {{ formatDate(conversation.created_at) }}
                            </span>
                        </div>

                        <!-- Messages -->
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            :class="[
                                'flex',
                                message.role === 'agent' ? 'justify-end' : 'justify-start'
                            ]"
                        >
                            <!-- Customer/System Avatar -->
                            <div v-if="message.role !== 'agent'" class="flex-shrink-0 mr-3">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center"
                                    :class="message.role === 'system' ? 'bg-gray-200' : 'bg-gradient-to-br from-primary-500 to-primary-700'"
                                >
                                    <CpuChipIcon v-if="message.role === 'system'" class="w-5 h-5 text-gray-500" />
                                    <span v-else class="text-white text-sm font-bold">{{ getInitials(customer) }}</span>
                                </div>
                            </div>

                            <!-- Message Bubble -->
                            <div
                                :class="[
                                    'max-w-[70%] rounded-2xl px-4 py-3 shadow-sm',
                                    message.role === 'agent'
                                        ? 'bg-primary-600 text-white rounded-br-md'
                                        : message.role === 'system'
                                            ? 'bg-gray-100 text-gray-600 rounded-bl-md italic text-sm'
                                            : 'bg-white text-gray-900 rounded-bl-md'
                                ]"
                            >
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>

                                <!-- Time & Status -->
                                <div class="flex items-center justify-end gap-1 mt-1">
                                    <span
                                        class="text-xs"
                                        :class="message.role === 'agent' ? 'text-white/60' : 'text-gray-400'"
                                    >
                                        {{ formatTime(message.created_at) }}
                                    </span>
                                    <template v-if="message.role === 'agent'">
                                        <CheckIcon v-if="!message.is_read" class="w-3 h-3 text-white/60" />
                                        <div v-else class="flex -space-x-1">
                                            <CheckIcon class="w-3 h-3 text-blue-300" />
                                            <CheckIcon class="w-3 h-3 text-blue-300" />
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Agent Avatar -->
                            <div v-if="message.role === 'agent'" class="flex-shrink-0 ml-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <UserIcon class="w-5 h-5 text-gray-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Typing Indicator -->
                        <div v-if="customerTyping" class="flex justify-start">
                            <div class="flex-shrink-0 mr-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-bold">{{ getInitials(customer) }}</span>
                                </div>
                            </div>
                            <div class="bg-white rounded-2xl rounded-bl-md px-4 py-3 shadow-sm">
                                <div class="flex gap-1">
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reply Input -->
                    <div class="border-t border-gray-100 p-4 bg-white">
                        <!-- Quick Replies -->
                        <div class="flex gap-2 mb-3 overflow-x-auto">
                            <button
                                v-for="reply in quickReplies"
                                :key="reply.id"
                                @click="useQuickReply(reply.text)"
                                class="flex-shrink-0 px-3 py-1.5 bg-gray-100 rounded-full text-xs font-medium text-gray-600 hover:bg-gray-200 transition"
                            >
                                {{ reply.label }}
                            </button>
                        </div>

                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <textarea
                                    ref="replyInput"
                                    v-model="replyText"
                                    @keydown.enter.exact.prevent="sendReply"
                                    @input="handleTyping"
                                    placeholder="Votre reponse..."
                                    rows="1"
                                    :disabled="conversation.status !== 'active'"
                                    class="w-full px-4 py-3 bg-gray-100 rounded-xl resize-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition max-h-32 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                ></textarea>
                            </div>
                            <button
                                @click="sendReply"
                                :disabled="!replyText.trim() || sending || conversation.status !== 'active'"
                                class="p-3 bg-primary-600 rounded-xl text-white hover:bg-primary-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <ArrowPathIcon v-if="sending" class="w-5 h-5 animate-spin" />
                                <PaperAirplaneIcon v-else class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Customer Info Sidebar -->
                <div class="w-80 flex-shrink-0 space-y-4">
                    <!-- Customer Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Informations client</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Nom</p>
                                <p class="text-sm font-medium text-gray-900">{{ customer?.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Email</p>
                                <p class="text-sm font-medium text-gray-900">{{ customer?.email }}</p>
                            </div>
                            <div v-if="customer?.phone">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Telephone</p>
                                <p class="text-sm font-medium text-gray-900">{{ customer?.phone }}</p>
                            </div>
                            <div v-if="customer?.company">
                                <p class="text-xs text-gray-500 uppercase tracking-wider">Entreprise</p>
                                <p class="text-sm font-medium text-gray-900">{{ customer?.company }}</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <Link
                                v-if="customer?.id"
                                :href="route('tenant.customers.show', customer.id)"
                                class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                            >
                                Voir le profil complet
                            </Link>
                        </div>
                    </div>

                    <!-- Customer History -->
                    <div v-if="customerHistory" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Historique</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <p class="text-2xl font-bold text-gray-900">{{ customerHistory.contracts_count }}</p>
                                <p class="text-xs text-gray-500">Contrats</p>
                            </div>
                            <div class="text-center p-3 bg-green-50 rounded-lg">
                                <p class="text-2xl font-bold text-green-600">{{ customerHistory.active_contracts }}</p>
                                <p class="text-xs text-gray-500">Actifs</p>
                            </div>
                            <div class="text-center p-3 bg-blue-50 rounded-lg">
                                <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(customerHistory.total_paid) }}</p>
                                <p class="text-xs text-gray-500">Total paye</p>
                            </div>
                            <div class="text-center p-3 bg-yellow-50 rounded-lg">
                                <p class="text-2xl font-bold text-yellow-600">{{ customerHistory.pending_invoices }}</p>
                                <p class="text-xs text-gray-500">Factures en attente</p>
                            </div>
                        </div>
                    </div>

                    <!-- Other Conversations -->
                    <div v-if="otherConversations?.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="font-semibold text-gray-900 mb-4">Autres conversations actives</h3>
                        <div class="space-y-2">
                            <Link
                                v-for="conv in otherConversations"
                                :key="conv.id"
                                :href="route('tenant.live-chat.show', conv.id)"
                                class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition"
                            >
                                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
                                    {{ getInitials(conv.customer) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ conv.customer?.first_name }} {{ conv.customer?.last_name }}
                                    </p>
                                </div>
                                <span
                                    v-if="conv.unread_count > 0"
                                    class="w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center"
                                >
                                    {{ conv.unread_count }}
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    PaperAirplaneIcon,
    CheckIcon,
    UserIcon,
    CpuChipIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    conversation: Object,
    messages: {
        type: Array,
        default: () => []
    },
    customer: Object,
    customerHistory: Object,
    otherConversations: Array,
    site: Object,
})

const messagesContainer = ref(null)
const replyInput = ref(null)
const replyText = ref('')
const sending = ref(false)
const customerTyping = ref(false)
const messages = ref(props.messages || [])

let typingTimeout = null
let echoChannel = null

const quickReplies = [
    { id: 1, label: 'Bonjour!', text: "Bonjour! Comment puis-je vous aider aujourd'hui?" },
    { id: 2, label: 'Je verifie...', text: 'Je verifie cela pour vous, un instant.' },
    { id: 3, label: 'Bien recu', text: 'Bien recu! Je prends en charge votre demande.' },
    { id: 4, label: 'A bientot!', text: "N'hesitez pas si vous avez d'autres questions. Bonne journee!" },
]

const getInitials = (obj) => {
    if (!obj) return '?'
    if (obj.name) {
        return obj.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    }
    const first = obj.first_name?.[0] || ''
    const last = obj.last_name?.[0] || ''
    return (first + last).toUpperCase() || '?'
}

const getStatusClass = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-700'
        case 'closed':
            return 'bg-gray-100 text-gray-600'
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
        default:
            return status
    }
}

const formatTime = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount || 0)
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

const sendReply = async () => {
    if (!replyText.value.trim() || sending.value) return

    sending.value = true
    const content = replyText.value.trim()

    // Optimistically add message
    const tempId = Date.now()
    messages.value.push({
        id: tempId,
        role: 'agent',
        content: content,
        created_at: new Date().toISOString(),
        is_read: false,
        sending: true
    })

    replyText.value = ''
    scrollToBottom()

    try {
        const response = await axios.post(route('tenant.live-chat.reply', props.conversation.id), {
            content: content
        })

        // Replace temp message with real one
        const index = messages.value.findIndex(m => m.id === tempId)
        if (index !== -1) {
            messages.value[index] = response.data.message
        }

        // Mark customer messages as read
        axios.post(route('tenant.live-chat.read', props.conversation.id)).catch(() => {})
    } catch (error) {
        console.error('Failed to send reply:', error)
        // Mark as failed
        const index = messages.value.findIndex(m => m.id === tempId)
        if (index !== -1) {
            messages.value[index].failed = true
        }
    } finally {
        sending.value = false
    }
}

const handleTyping = () => {
    if (typingTimeout) clearTimeout(typingTimeout)

    axios.post(route('tenant.live-chat.typing', props.conversation.id), {
        is_typing: true
    }).catch(() => {})

    typingTimeout = setTimeout(() => {
        axios.post(route('tenant.live-chat.typing', props.conversation.id), {
            is_typing: false
        }).catch(() => {})
    }, 2000)
}

const useQuickReply = (text) => {
    replyText.value = text
    replyInput.value?.focus()
}

const closeConversation = async () => {
    if (!confirm('Etes-vous sur de vouloir fermer cette conversation?')) return

    try {
        await axios.post(route('tenant.live-chat.close', props.conversation.id))
        router.reload()
    } catch (error) {
        console.error('Failed to close conversation:', error)
    }
}

const reopenConversation = async () => {
    try {
        await axios.post(route('tenant.live-chat.reopen', props.conversation.id))
        router.reload()
    } catch (error) {
        console.error('Failed to reopen conversation:', error)
    }
}

// Setup Echo for real-time updates
const setupEcho = () => {
    if (!window.Echo || !props.conversation) return

    echoChannel = window.Echo.private(`chat.${props.conversation.conversation_id}`)
        .listen('.message.sent', (data) => {
            if (data.sender_type === 'customer') {
                messages.value.push(data.message)
                scrollToBottom()

                // Mark as read
                axios.post(route('tenant.live-chat.read', props.conversation.id)).catch(() => {})
            }
        })
        .listen('.typing', (data) => {
            if (data.typer_type === 'customer') {
                customerTyping.value = data.is_typing
            }
        })
        .listen('.messages.read', (data) => {
            if (data.reader_type === 'customer') {
                messages.value.forEach(msg => {
                    if (data.message_ids.includes(msg.id)) {
                        msg.is_read = true
                    }
                })
            }
        })
}

onMounted(() => {
    scrollToBottom()
    setupEcho()

    // Mark messages as read on mount
    if (props.conversation) {
        axios.post(route('tenant.live-chat.read', props.conversation.id)).catch(() => {})
    }
})

onUnmounted(() => {
    if (typingTimeout) clearTimeout(typingTimeout)

    if (echoChannel && props.conversation) {
        window.Echo.leave(`chat.${props.conversation.conversation_id}`)
    }
})
</script>
