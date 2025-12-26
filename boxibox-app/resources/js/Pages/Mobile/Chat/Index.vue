<template>
    <MobileLayout title="Support" :show-back="true">
        <!-- Chat Header with Agent Info -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
            <div class="flex items-center">
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                        <UserIcon class="w-7 h-7 text-white" />
                    </div>
                    <span
                        class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white"
                        :class="agentOnline ? 'bg-green-500' : 'bg-gray-400'"
                    ></span>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="font-bold text-gray-900">Support Boxibox</h3>
                    <p v-if="agentOnline" class="text-sm text-green-600 flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        En ligne - Repond en ~2 min
                    </p>
                    <p v-else class="text-sm text-gray-500">
                        Hors ligne - Reponse sous 24h
                    </p>
                </div>
                <button @click="startCall" class="p-3 bg-green-100 rounded-xl text-green-600 hover:bg-green-200 transition">
                    <PhoneIcon class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Connection Status -->
        <div v-if="!isConnected && conversation" class="mb-4 p-3 bg-yellow-50 rounded-xl flex items-center gap-2 text-sm text-yellow-700">
            <ExclamationCircleIcon class="w-5 h-5" />
            Reconnexion en cours...
        </div>

        <!-- Quick Actions (only if no messages yet) -->
        <div v-if="messages.length <= 1" class="flex gap-2 mb-4 overflow-x-auto pb-2 -mx-4 px-4">
            <button
                v-for="action in quickActions"
                :key="action.id"
                @click="sendQuickMessage(action.message)"
                class="flex-shrink-0 px-4 py-2 bg-white rounded-full text-sm font-medium text-gray-700 shadow-sm border border-gray-100 hover:bg-gray-50 active:scale-95 transition"
            >
                {{ action.label }}
            </button>
        </div>

        <!-- Messages Container -->
        <div
            ref="messagesContainer"
            class="flex-1 space-y-4 mb-4 max-h-[calc(100vh-380px)] overflow-y-auto"
            @scroll="handleScroll"
        >
            <!-- Loading older messages -->
            <div v-if="loadingMore" class="text-center py-2">
                <div class="inline-flex items-center gap-2 text-sm text-gray-500">
                    <ArrowPathIcon class="w-4 h-4 animate-spin" />
                    Chargement...
                </div>
            </div>

            <!-- Date separator -->
            <div class="flex items-center justify-center">
                <span class="px-4 py-1 bg-gray-100 rounded-full text-xs font-medium text-gray-500">
                    {{ formatDate(new Date()) }}
                </span>
            </div>

            <!-- Messages -->
            <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="message in messages"
                    :key="message.id"
                    :class="[
                        'flex',
                        message.role === 'customer' ? 'justify-end' : 'justify-start'
                    ]"
                >
                    <!-- Agent Avatar -->
                    <div v-if="message.role !== 'customer'" class="flex-shrink-0 mr-2">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center"
                            :class="message.role === 'system' ? 'bg-gray-200' : 'bg-gradient-to-br from-primary-500 to-primary-700'"
                        >
                            <CpuChipIcon v-if="message.role === 'system'" class="w-4 h-4 text-gray-500" />
                            <UserIcon v-else class="w-4 h-4 text-white" />
                        </div>
                    </div>

                    <!-- Message Bubble -->
                    <div
                        :class="[
                            'max-w-[80%] rounded-2xl px-4 py-3 shadow-sm',
                            message.role === 'customer'
                                ? 'bg-gradient-to-br from-primary-600 to-primary-700 text-white rounded-br-md'
                                : message.role === 'system'
                                    ? 'bg-gray-100 text-gray-600 rounded-bl-md italic'
                                    : 'bg-white text-gray-900 rounded-bl-md'
                        ]"
                    >
                        <!-- Message content -->
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>

                        <!-- Attachments -->
                        <div v-if="message.metadata?.attachment" class="mt-2">
                            <div
                                class="flex items-center gap-2 p-2 rounded-lg"
                                :class="message.role === 'customer' ? 'bg-white/10' : 'bg-gray-100'"
                            >
                                <DocumentIcon class="w-5 h-5" :class="message.role === 'customer' ? 'text-white/70' : 'text-gray-500'" />
                                <span class="text-xs truncate" :class="message.role === 'customer' ? 'text-white/90' : 'text-gray-700'">
                                    {{ message.metadata.attachment.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Time & Status -->
                        <div class="flex items-center justify-end gap-1 mt-1">
                            <span
                                class="text-xs"
                                :class="message.role === 'customer' ? 'text-white/60' : 'text-gray-400'"
                            >
                                {{ formatTime(message.created_at) }}
                            </span>
                            <template v-if="message.role === 'customer'">
                                <CheckIcon v-if="!message.is_read" class="w-3 h-3 text-white/60" />
                                <div v-else class="flex -space-x-1">
                                    <CheckIcon class="w-3 h-3 text-blue-300" />
                                    <CheckIcon class="w-3 h-3 text-blue-300" />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </TransitionGroup>

            <!-- Typing indicator -->
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="agentTyping" class="flex justify-start">
                    <div class="flex-shrink-0 mr-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-full flex items-center justify-center">
                            <UserIcon class="w-4 h-4 text-white" />
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
            </Transition>
        </div>

        <!-- Input Area -->
        <div class="fixed bottom-20 left-0 right-0 bg-white border-t border-gray-100 p-4 shadow-lg">
            <!-- Attachment Preview -->
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="selectedFile" class="mb-3 p-3 bg-gray-100 rounded-xl flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <DocumentIcon class="w-5 h-5 text-gray-500" />
                        <span class="text-sm text-gray-700 truncate max-w-[200px]">{{ selectedFile.name }}</span>
                    </div>
                    <button @click="removeFile" class="p-1 hover:bg-gray-200 rounded-lg transition">
                        <XMarkIcon class="w-4 h-4 text-gray-500" />
                    </button>
                </div>
            </Transition>

            <div class="flex items-end gap-3">
                <!-- Attachment Button -->
                <button
                    @click="triggerFileInput"
                    class="p-3 bg-gray-100 rounded-xl text-gray-600 hover:bg-gray-200 active:scale-95 transition"
                >
                    <PaperClipIcon class="w-5 h-5" />
                </button>
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    accept="image/*,.pdf,.doc,.docx"
                    @change="handleFileSelect"
                />

                <!-- Message Input -->
                <div class="flex-1 relative">
                    <textarea
                        ref="messageInput"
                        v-model="newMessage"
                        @keydown.enter.exact.prevent="sendMessage"
                        @input="handleTyping"
                        placeholder="Votre message..."
                        rows="1"
                        class="w-full px-4 py-3 bg-gray-100 rounded-2xl resize-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition max-h-32 text-sm"
                    ></textarea>
                </div>

                <!-- Send Button -->
                <button
                    @click="sendMessage"
                    :disabled="!canSend || sending"
                    class="p-3 bg-gradient-to-br from-primary-600 to-primary-700 rounded-xl text-white shadow-lg shadow-primary-500/30 hover:shadow-xl active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <ArrowPathIcon v-if="sending" class="w-5 h-5 animate-spin" />
                    <PaperAirplaneIcon v-else class="w-5 h-5" />
                </button>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    UserIcon,
    PhoneIcon,
    PaperClipIcon,
    PaperAirplaneIcon,
    DocumentIcon,
    CheckIcon,
    XMarkIcon,
    ArrowPathIcon,
    ExclamationCircleIcon,
    CpuChipIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    conversation: Object,
    messages: {
        type: Array,
        default: () => []
    },
    customer: Object,
})

const page = usePage()
const messagesContainer = ref(null)
const messageInput = ref(null)
const fileInput = ref(null)
const newMessage = ref('')
const selectedFile = ref(null)
const agentTyping = ref(false)
const agentOnline = ref(true)
const loadingMore = ref(false)
const sending = ref(false)
const isConnected = ref(true)

const messages = ref(props.messages || [])
const conversation = ref(props.conversation)

let typingTimeout = null
let echoChannel = null

const quickActions = [
    { id: 1, label: 'Probleme d\'acces', message: 'Bonjour, j\'ai un probleme pour acceder a mon box.' },
    { id: 2, label: 'Question facture', message: 'Bonjour, j\'ai une question concernant ma derniere facture.' },
    { id: 3, label: 'Changer de box', message: 'Bonjour, je souhaite changer de box pour une taille differente.' },
    { id: 4, label: 'Resilier', message: 'Bonjour, je souhaite des informations sur la resiliation de mon contrat.' },
]

const canSend = computed(() => {
    return newMessage.value.trim().length > 0 || selectedFile.value
})

const formatTime = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatDate = (date) => {
    const today = new Date()
    const msgDate = new Date(date)

    if (msgDate.toDateString() === today.toDateString()) {
        return "Aujourd'hui"
    }

    return msgDate.toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long'
    })
}

const autoResize = () => {
    const textarea = messageInput.value
    if (textarea) {
        textarea.style.height = 'auto'
        textarea.style.height = Math.min(textarea.scrollHeight, 128) + 'px'
    }
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

// Start or get conversation
const ensureConversation = async () => {
    if (conversation.value) return conversation.value.conversation_id

    try {
        const response = await axios.post(route('mobile.chat.store'))
        conversation.value = response.data.conversation
        setupEchoChannel()
        return conversation.value.conversation_id
    } catch (error) {
        console.error('Failed to create conversation:', error)
        return null
    }
}

// Send message to API
const sendMessage = async () => {
    if (!canSend.value || sending.value) return

    const content = newMessage.value.trim()
    const file = selectedFile.value

    // Ensure we have a conversation
    const conversationId = await ensureConversation()
    if (!conversationId) {
        alert('Impossible de demarrer la conversation. Veuillez reessayer.')
        return
    }

    sending.value = true

    // Optimistically add message
    const tempId = Date.now()
    const tempMessage = {
        id: tempId,
        conversation_id: conversationId,
        role: 'customer',
        content: content,
        metadata: file ? { attachment: { name: file.name } } : {},
        created_at: new Date().toISOString(),
        is_read: false,
        sending: true
    }
    messages.value.push(tempMessage)

    // Clear input
    newMessage.value = ''
    selectedFile.value = null
    autoResize()
    scrollToBottom()

    try {
        // Prepare form data for file upload
        const formData = new FormData()
        formData.append('content', content)
        if (file) {
            formData.append('attachment', file)
        }

        const response = await axios.post(
            route('mobile.chat.send', { conversationId }),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        )

        // Replace temp message with real one
        const index = messages.value.findIndex(m => m.id === tempId)
        if (index !== -1) {
            messages.value[index] = response.data.message
        }
    } catch (error) {
        console.error('Failed to send message:', error)
        // Mark message as failed
        const index = messages.value.findIndex(m => m.id === tempId)
        if (index !== -1) {
            messages.value[index].failed = true
            messages.value[index].sending = false
        }
    } finally {
        sending.value = false
    }
}

// Handle typing indicator
const handleTyping = () => {
    autoResize()

    if (!conversation.value) return

    // Clear previous timeout
    if (typingTimeout) {
        clearTimeout(typingTimeout)
    }

    // Send typing indicator
    axios.post(route('mobile.chat.typing', { conversationId: conversation.value.conversation_id }), {
        is_typing: true
    }).catch(() => {})

    // Stop typing after 2 seconds of inactivity
    typingTimeout = setTimeout(() => {
        axios.post(route('mobile.chat.typing', { conversationId: conversation.value.conversation_id }), {
            is_typing: false
        }).catch(() => {})
    }, 2000)
}

const sendQuickMessage = (message) => {
    newMessage.value = message
    sendMessage()
}

const triggerFileInput = () => {
    fileInput.value?.click()
}

const handleFileSelect = (event) => {
    const file = event.target.files?.[0]
    if (file) {
        selectedFile.value = file
    }
}

const removeFile = () => {
    selectedFile.value = null
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const handleScroll = () => {
    if (messagesContainer.value?.scrollTop === 0 && !loadingMore.value) {
        // Load older messages - TODO: implement pagination
        loadingMore.value = true
        setTimeout(() => {
            loadingMore.value = false
        }, 1000)
    }
}

const startCall = () => {
    window.location.href = 'tel:0800123456'
}

// Setup Laravel Echo for real-time updates
const setupEchoChannel = () => {
    if (!conversation.value || !window.Echo) return

    const channelName = `chat.${conversation.value.conversation_id}`

    echoChannel = window.Echo.private(channelName)
        .listen('.message.sent', (data) => {
            // Only add if not from customer (our own message)
            if (data.sender_type !== 'customer') {
                messages.value.push(data.message)
                scrollToBottom()

                // Mark as read
                axios.post(route('mobile.chat.read', { conversationId: conversation.value.conversation_id }))
                    .catch(() => {})
            }
        })
        .listen('.typing', (data) => {
            if (data.typer_type === 'agent') {
                agentTyping.value = data.is_typing
            }
        })
        .listen('.messages.read', (data) => {
            if (data.reader_type === 'agent') {
                // Update our messages as read
                messages.value.forEach(msg => {
                    if (data.message_ids.includes(msg.id)) {
                        msg.is_read = true
                    }
                })
            }
        })

    // Track connection status
    window.Echo.connector.pusher.connection.bind('connected', () => {
        isConnected.value = true
    })

    window.Echo.connector.pusher.connection.bind('disconnected', () => {
        isConnected.value = false
    })
}

onMounted(() => {
    scrollToBottom()

    // Setup Echo if conversation exists
    if (conversation.value) {
        setupEchoChannel()
    }
})

onUnmounted(() => {
    // Cleanup
    if (typingTimeout) {
        clearTimeout(typingTimeout)
    }

    if (echoChannel && conversation.value) {
        window.Echo.leave(`chat.${conversation.value.conversation_id}`)
    }
})
</script>
