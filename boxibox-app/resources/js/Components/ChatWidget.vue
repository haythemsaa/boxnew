<template>
    <div class="chatbot-widget">
        <!-- Toggle Button -->
        <button
            v-if="!isOpen"
            @click="openChat"
            class="chat-toggle-btn"
            :class="{ 'has-notification': hasUnread }"
            aria-label="Ouvrir le chat d'assistance"
            :aria-expanded="isOpen"
        >
            <ChatBubbleLeftRightIcon class="w-6 h-6" aria-hidden="true" />
            <span v-if="hasUnread" class="notification-badge" aria-hidden="true">1</span>
        </button>

        <!-- Chat Window -->
        <Transition name="chat-slide">
            <div v-if="isOpen" class="chat-window" role="dialog" aria-modal="true" aria-labelledby="chat-title">
                <!-- Header -->
                <div class="chat-header">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center" aria-hidden="true">
                            <SparklesIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 id="chat-title" class="font-semibold text-white">Assistant BoxiBox</h3>
                            <p class="text-xs text-white/70">
                                <span class="inline-block w-2 h-2 bg-green-400 rounded-full mr-1" aria-hidden="true"></span>
                                <span class="sr-only">Statut:</span> En ligne
                            </p>
                        </div>
                    </div>
                    <button @click="closeChat" class="text-white/70 hover:text-white transition focus:outline-none focus:ring-2 focus:ring-white/50 rounded" aria-label="Fermer le chat">
                        <XMarkIcon class="w-5 h-5" aria-hidden="true" />
                    </button>
                </div>

                <!-- Messages -->
                <div ref="messagesContainer" class="chat-messages" aria-live="polite" aria-label="Historique des messages">
                    <!-- Welcome Message -->
                    <div v-if="messages.length === 0" class="welcome-message">
                        <div class="text-center mb-4">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-primary-500 to-emerald-500 flex items-center justify-center">
                                <SparklesIcon class="w-8 h-8 text-white" />
                            </div>
                            <h4 class="font-semibold text-gray-900">Bonjour ! 👋</h4>
                            <p class="text-sm text-gray-500 mt-1">Comment puis-je vous aider ?</p>
                        </div>
                        <div class="quick-actions">
                            <button
                                v-for="action in quickActions"
                                :key="action.text"
                                @click="sendQuickAction(action.text)"
                                class="quick-action-btn"
                            >
                                <component :is="action.icon" class="w-4 h-4" />
                                {{ action.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Message List -->
                    <template v-for="(message, index) in messages" :key="index">
                        <div
                            :class="[
                                'message',
                                message.role === 'user' ? 'message-user' : 'message-assistant'
                            ]"
                        >
                            <div v-if="message.role === 'assistant'" class="message-avatar">
                                <SparklesIcon class="w-4 h-4 text-primary-600" />
                            </div>
                            <div class="message-content" v-html="formatMessage(message.content)"></div>
                        </div>

                        <!-- Suggested Actions -->
                        <div
                            v-if="message.role === 'assistant' && message.actions && index === messages.length - 1"
                            class="suggested-actions"
                        >
                            <button
                                v-for="action in message.actions"
                                :key="action.action"
                                @click="handleAction(action)"
                                class="action-btn"
                            >
                                {{ action.label }}
                            </button>
                        </div>
                    </template>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="message message-assistant" role="status" aria-label="L'assistant est en train de taper">
                        <div class="message-avatar" aria-hidden="true">
                            <SparklesIcon class="w-4 h-4 text-primary-600" />
                        </div>
                        <div class="typing-indicator" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="sr-only">L'assistant écrit une réponse...</span>
                    </div>
                </div>

                <!-- Input -->
                <div class="chat-input">
                    <label for="chat-input-field" class="sr-only">Votre message</label>
                    <input
                        id="chat-input-field"
                        v-model="inputMessage"
                        @keyup.enter="sendMessage"
                        type="text"
                        placeholder="Tapez votre message..."
                        :disabled="isTyping"
                        :aria-disabled="isTyping"
                        class="chat-input-field"
                        autocomplete="off"
                    />
                    <button
                        @click="sendMessage"
                        :disabled="!inputMessage.trim() || isTyping"
                        :aria-disabled="!inputMessage.trim() || isTyping"
                        class="send-btn"
                        aria-label="Envoyer le message"
                    >
                        <PaperAirplaneIcon class="w-5 h-5" aria-hidden="true" />
                    </button>
                </div>

                <!-- Footer -->
                <div class="chat-footer">
                    <button @click="requestHumanAgent" class="text-xs text-gray-500 hover:text-primary-600 transition">
                        Parler à un conseiller
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import {
    ChatBubbleLeftRightIcon,
    XMarkIcon,
    PaperAirplaneIcon,
    SparklesIcon,
    CurrencyEuroIcon,
    ArchiveBoxIcon,
    CalendarIcon,
    QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tenantId: {
        type: Number,
        required: true
    },
    siteId: {
        type: Number,
        default: null
    },
    customerId: {
        type: Number,
        default: null
    },
    locale: {
        type: String,
        default: 'fr'
    },
    primaryColor: {
        type: String,
        default: '#8fbd56'
    }
})

const emit = defineEmits(['open', 'close', 'message-sent', 'action'])

const isOpen = ref(false)
const isTyping = ref(false)
const hasUnread = ref(false)
const inputMessage = ref('')
const messages = ref([])
const conversationId = ref(null)
const messagesContainer = ref(null)

const quickActions = [
    { icon: CurrencyEuroIcon, label: 'Voir les tarifs', text: 'Quels sont vos tarifs ?' },
    { icon: ArchiveBoxIcon, label: 'Disponibilités', text: 'Avez-vous des boxes disponibles ?' },
    { icon: CalendarIcon, label: 'Réserver', text: 'Je souhaite réserver un box' },
    { icon: QuestionMarkCircleIcon, label: 'Aide taille', text: 'Quelle taille de box me conseillez-vous ?' },
]

const openChat = () => {
    isOpen.value = true
    hasUnread.value = false
    emit('open')

    // Auto-greeting if first open
    if (messages.value.length === 0) {
        // Welcome message is shown via template
    }
}

const closeChat = () => {
    isOpen.value = false
    emit('close')
}

const sendQuickAction = (text) => {
    inputMessage.value = text
    sendMessage()
}

const sendMessage = async () => {
    const message = inputMessage.value.trim()
    if (!message || isTyping.value) return

    // Add user message
    messages.value.push({
        role: 'user',
        content: message
    })

    inputMessage.value = ''
    isTyping.value = true

    await scrollToBottom()

    try {
        const response = await fetch('/api/v1/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                conversation_id: conversationId.value,
                tenant_id: props.tenantId,
                site_id: props.siteId,
                customer_id: props.customerId,
                locale: props.locale,
            })
        })

        const data = await response.json()

        if (data.success) {
            conversationId.value = data.conversation_id

            // Add assistant response
            messages.value.push({
                role: 'assistant',
                content: data.response,
                actions: data.suggested_actions,
                intent: data.intent,
            })

            emit('message-sent', {
                message,
                response: data.response,
                intent: data.intent,
            })
        } else {
            messages.value.push({
                role: 'assistant',
                content: 'Désolé, une erreur s\'est produite. Veuillez réessayer.',
            })
        }
    } catch (error) {
        console.error('Chatbot error:', error)
        messages.value.push({
            role: 'assistant',
            content: 'Désolé, je ne peux pas répondre pour le moment. Veuillez réessayer plus tard.',
        })
    } finally {
        isTyping.value = false
        await scrollToBottom()
    }
}

const handleAction = (action) => {
    emit('action', action)

    switch (action.action) {
        case 'start_booking':
            // Could open booking modal or redirect
            window.location.href = `/book/${props.tenantId}`
            break
        case 'check_availability':
            sendQuickAction('Quels boxes sont disponibles ?')
            break
        case 'show_pricing':
            sendQuickAction('Quels sont vos tarifs ?')
            break
        case 'size_calculator':
            sendQuickAction('Aidez-moi à choisir la taille de mon box')
            break
        case 'contact_advisor':
            requestHumanAgent()
            break
        default:
            // Generic action handling
            sendQuickAction(action.label)
    }
}

const requestHumanAgent = async () => {
    messages.value.push({
        role: 'assistant',
        content: '📞 Je vais transférer votre demande à un conseiller. Pourriez-vous me donner votre nom et email pour qu\'il puisse vous recontacter ?',
    })

    await scrollToBottom()
}

const formatMessage = (content) => {
    if (!content) return ''

    // Convert markdown-style bold to HTML
    let formatted = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')

    // Convert newlines to <br>
    formatted = formatted.replace(/\n/g, '<br>')

    // Convert bullet points
    formatted = formatted.replace(/^• /gm, '<span class="bullet">•</span> ')

    return formatted
}

const scrollToBottom = async () => {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

onMounted(() => {
    // Generate conversation ID if not exists
    if (!conversationId.value) {
        conversationId.value = 'conv_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    }
})
</script>

<style scoped>
.chatbot-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: system-ui, -apple-system, sans-serif;
}

.chat-toggle-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8fbd56 0%, #38cab3 100%);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(143, 189, 86, 0.4);
    transition: all 0.3s ease;
    position: relative;
}

.chat-toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(143, 189, 86, 0.5);
}

.chat-toggle-btn.has-notification::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 16px;
    height: 16px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid white;
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 20px;
    height: 20px;
    background: #ef4444;
    border-radius: 50%;
    font-size: 11px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

.chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    height: 560px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

@media (max-width: 480px) {
    .chat-window {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        border-radius: 0;
    }

    .chatbot-widget {
        bottom: 16px;
        right: 16px;
    }
}

.chat-header {
    background: linear-gradient(135deg, #8fbd56 0%, #38cab3 100%);
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f9fafb;
}

.welcome-message {
    padding: 20px;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background: #f3f4f6;
    border-color: #8fbd56;
    color: #8fbd56;
}

.message {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    max-width: 85%;
}

.message-user {
    margin-left: auto;
    flex-direction: row-reverse;
}

.message-assistant {
    margin-right: auto;
}

.message-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f0fdf4;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.message-content {
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 14px;
    line-height: 1.5;
}

.message-user .message-content {
    background: linear-gradient(135deg, #8fbd56 0%, #38cab3 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-assistant .message-content {
    background: white;
    color: #374151;
    border: 1px solid #e5e7eb;
    border-bottom-left-radius: 4px;
}

.message-content :deep(.bullet) {
    color: #8fbd56;
    font-weight: bold;
}

.suggested-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-left: 36px;
    margin-bottom: 12px;
}

.action-btn {
    padding: 6px 12px;
    background: white;
    border: 1px solid #8fbd56;
    border-radius: 16px;
    font-size: 12px;
    color: #8fbd56;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    background: #8fbd56;
    color: white;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #9ca3af;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: 0s; }
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-4px); }
}

.chat-input {
    padding: 12px;
    background: white;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 8px;
}

.chat-input-field {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.chat-input-field:focus {
    border-color: #8fbd56;
}

.send-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8fbd56 0%, #38cab3 100%);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.send-btn:hover:not(:disabled) {
    transform: scale(1.05);
}

.send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.chat-footer {
    padding: 8px;
    text-align: center;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

/* Transitions */
.chat-slide-enter-active,
.chat-slide-leave-active {
    transition: all 0.3s ease;
}

.chat-slide-enter-from,
.chat-slide-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
}
</style>
