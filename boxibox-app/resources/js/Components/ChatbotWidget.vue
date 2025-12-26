<template>
    <div>
        <!-- Chat Button -->
        <button
            v-if="!isOpen"
            @click="openChat"
            class="fixed bottom-6 right-6 w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 text-white rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 z-50 group"
            :class="{ 'animate-bounce': hasNewMessage }"
        >
            <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center animate-pulse">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
            <!-- Tooltip -->
            <span class="absolute right-20 bg-gray-900 text-white text-sm px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                Besoin d'aide?
            </span>
        </button>

        <!-- Chat Window -->
        <transition name="slide-up">
            <div
                v-if="isOpen"
                class="fixed bottom-6 right-6 w-full sm:w-[400px] h-[calc(100vh-3rem)] sm:h-[600px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden z-50"
                :class="{ 'left-0 right-0 bottom-0 mx-auto': isMobile }"
            >
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white p-4 flex items-center justify-between relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent animate-shimmer"></div>
                    <div class="flex items-center space-x-3 relative z-10">
                        <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center ring-2 ring-white/30">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Assistant BoxiBox</h3>
                            <p class="text-xs text-blue-100 flex items-center gap-1">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                En ligne • Répond instantanément
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 relative z-10">
                        <button
                            @click="toggleSound"
                            class="p-2 hover:bg-white/10 rounded-full transition"
                            :title="soundEnabled ? 'Désactiver le son' : 'Activer le son'"
                        >
                            <svg v-if="soundEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </button>
                        <button @click="closeChat" class="p-2 hover:bg-white/10 rounded-full transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gradient-to-b from-gray-50 to-white">
                    <!-- Welcome Message -->
                    <div v-if="messages.length === 0" class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-md max-w-[85%] border border-gray-100">
                                <p class="text-gray-800 font-medium">
                                    Bonjour! Je suis votre assistant IA BoxiBox
                                </p>
                                <p class="text-sm text-gray-600 mt-2">
                                    Je peux vous aider avec:
                                </p>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <button
                                        v-for="topic in helpTopics"
                                        :key="topic.id"
                                        @click="sendMessage(topic.query)"
                                        class="flex items-center gap-2 p-2 bg-gray-50 hover:bg-blue-50 rounded-xl text-sm text-gray-700 hover:text-blue-700 transition-colors text-left"
                                    >
                                        <span class="text-lg">{{ topic.icon }}</span>
                                        <span>{{ topic.label }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message History -->
                    <template v-for="(message, index) in messages" :key="index">
                        <!-- User Message -->
                        <div v-if="message.role === 'user'" class="flex justify-end">
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl rounded-tr-none px-4 py-3 shadow-md max-w-[80%]">
                                <p class="text-sm">{{ message.content }}</p>
                                <p class="text-xs text-blue-200 mt-1 text-right">{{ formatTime(message.timestamp) }}</p>
                            </div>
                        </div>

                        <!-- Assistant Message -->
                        <div v-else class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="max-w-[85%]">
                                <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-md border border-gray-100">
                                    <div class="text-sm text-gray-800 prose prose-sm" v-html="renderMarkdown(message.content)"></div>
                                    <p class="text-xs text-gray-400 mt-2">{{ formatTime(message.timestamp) }}</p>
                                </div>

                                <!-- Suggested Actions -->
                                <div v-if="message.suggested_actions && message.suggested_actions.length > 0" class="flex flex-wrap gap-2 mt-2">
                                    <button
                                        v-for="action in message.suggested_actions"
                                        :key="action.label"
                                        @click="handleAction(action)"
                                        class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs rounded-full hover:bg-blue-100 transition-colors border border-blue-200"
                                    >
                                        {{ action.label }}
                                    </button>
                                </div>

                                <!-- Feedback -->
                                <div v-if="!message.rated && index === messages.length - 1" class="flex items-center gap-2 mt-2">
                                    <span class="text-xs text-gray-500">Cette réponse vous a aidé?</span>
                                    <button @click="rateResponse(index, 'positive')" class="p-1 hover:bg-green-50 rounded" title="Oui">
                                        <svg class="w-4 h-4 text-gray-400 hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                        </svg>
                                    </button>
                                    <button @click="rateResponse(index, 'negative')" class="p-1 hover:bg-red-50 rounded" title="Non">
                                        <svg class="w-4 h-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-md border border-gray-100">
                            <div class="flex space-x-1.5">
                                <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.15s;"></div>
                                <div class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0.3s;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-4 bg-white border-t border-gray-100">
                    <form @submit.prevent="handleSubmit" class="flex items-center gap-2">
                        <!-- Voice Input Button -->
                        <button
                            v-if="speechSupported"
                            type="button"
                            @click="toggleVoiceInput"
                            class="p-2 rounded-full transition-colors"
                            :class="isListening ? 'bg-red-100 text-red-600 animate-pulse' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            :title="isListening ? 'Arrêter' : 'Parler'"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                        </button>

                        <!-- Text Input -->
                        <div class="flex-1 relative">
                            <input
                                v-model="inputMessage"
                                type="text"
                                placeholder="Tapez votre message..."
                                class="w-full px-4 py-3 pr-10 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-shadow"
                                :disabled="isTyping"
                                @keydown.enter.exact="handleSubmit"
                            />
                            <!-- Character count -->
                            <span v-if="inputMessage.length > 100" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400">
                                {{ inputMessage.length }}/500
                            </span>
                        </div>

                        <!-- Send Button -->
                        <button
                            type="submit"
                            :disabled="!inputMessage.trim() || isTyping"
                            class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 disabled:from-gray-300 disabled:to-gray-300 disabled:cursor-not-allowed text-white rounded-full flex items-center justify-center transition-all shadow-lg hover:shadow-xl disabled:shadow-none"
                        >
                            <svg v-if="!isTyping" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>

                    <!-- Footer -->
                    <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                        <span class="flex items-center gap-1">
                            <span :class="['w-2 h-2 rounded-full', aiProvider.has_api_key ? 'bg-green-400' : 'bg-amber-400']"></span>
                            {{ providerDisplayName }}
                        </span>
                        <button @click="startNewConversation" class="hover:text-gray-600 transition-colors">
                            Nouvelle conversation
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, nextTick, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import { marked } from 'marked';

// State
const isOpen = ref(false);
const messages = ref([]);
const inputMessage = ref('');
const aiProvider = ref({ provider: 'loading', model: '', has_api_key: false });
const isTyping = ref(false);
const unreadCount = ref(0);
const conversationId = ref(null);
const messagesContainer = ref(null);
const soundEnabled = ref(true);
const hasNewMessage = ref(false);
const isListening = ref(false);
const recognition = ref(null);
const isMobile = ref(false);

// Speech recognition support
const speechSupported = ref(false);

// Help topics for quick start
const helpTopics = [
    { id: 'prices', icon: '💰', label: 'Voir les tarifs', query: 'Quels sont vos tarifs?' },
    { id: 'size', icon: '📏', label: 'Quelle taille?', query: 'Quelle taille de box me faut-il?' },
    { id: 'availability', icon: '📦', label: 'Disponibilités', query: 'Avez-vous des boxes disponibles?' },
    { id: 'access', icon: '🔑', label: 'Accès au site', query: 'Comment fonctionne l\'accès?' },
];

// Configure marked for safe HTML
marked.setOptions({
    breaks: true,
    gfm: true,
});

// Computed
const providerDisplayName = computed(() => {
    const names = {
        groq: 'Groq AI',
        gemini: 'Google Gemini',
        openrouter: 'OpenRouter',
        openai: 'OpenAI GPT',
        anthropic: 'Claude AI',
        fallback: 'IA locale',
        loading: 'Chargement...',
    };
    return names[aiProvider.value.provider] || 'Assistant IA';
});

// Methods
const openChat = () => {
    isOpen.value = true;
    unreadCount.value = 0;
    hasNewMessage.value = false;
};

const closeChat = () => {
    isOpen.value = false;
};

const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value;
};

const playNotificationSound = () => {
    if (!soundEnabled.value) return;
    // Simple beep using Web Audio API
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.1;
        oscillator.start();
        setTimeout(() => oscillator.stop(), 100);
    } catch (e) {
        // Audio not supported
    }
};

const handleSubmit = async () => {
    if (!inputMessage.value.trim() || isTyping.value) return;

    const userMessage = inputMessage.value.trim();
    inputMessage.value = '';

    // Add user message
    messages.value.push({
        role: 'user',
        content: userMessage,
        timestamp: new Date(),
    });

    scrollToBottom();
    isTyping.value = true;

    try {
        const response = await axios.post('/api/chatbot', {
            message: userMessage,
            conversation_id: conversationId.value,
        });

        conversationId.value = response.data.conversation_id;

        // Add assistant response
        messages.value.push({
            role: 'assistant',
            content: response.data.message,
            timestamp: new Date(),
            suggested_actions: response.data.suggested_actions || [],
            rated: false,
        });

        playNotificationSound();

    } catch (error) {
        console.error('Chatbot error:', error);
        messages.value.push({
            role: 'assistant',
            content: 'Désolé, une erreur s\'est produite. Veuillez réessayer ou contacter notre équipe.',
            timestamp: new Date(),
        });
    } finally {
        isTyping.value = false;
        scrollToBottom();
    }
};

const sendMessage = (message) => {
    inputMessage.value = message;
    handleSubmit();
};

const handleAction = (action) => {
    // Handle different action types
    switch (action.action) {
        case 'start_booking':
            window.open('/book', '_blank');
            break;
        case 'check_availability':
            sendMessage('Quels boxes sont disponibles?');
            break;
        case 'show_pricing':
            sendMessage('Montrez-moi vos tarifs');
            break;
        case 'size_calculator':
            sendMessage('J\'ai besoin d\'aide pour choisir la taille');
            break;
        case 'contact_advisor':
            sendMessage('Je souhaite parler à un conseiller');
            break;
        default:
            if (action.label) {
                sendMessage(action.label);
            }
    }
};

const rateResponse = async (index, rating) => {
    messages.value[index].rated = true;

    try {
        await axios.post('/api/v1/chatbot/feedback', {
            conversation_id: conversationId.value,
            rating: rating,
            message_index: index,
        });
    } catch (e) {
        // Silent fail for feedback
    }
};

const startNewConversation = () => {
    messages.value = [];
    conversationId.value = null;
};

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const formatTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const renderMarkdown = (text) => {
    if (!text) return '';
    // Sanitize and render markdown
    return marked.parse(text);
};

// Voice input
const toggleVoiceInput = () => {
    if (isListening.value) {
        recognition.value?.stop();
        isListening.value = false;
    } else {
        startVoiceInput();
    }
};

const startVoiceInput = () => {
    if (!speechSupported.value) return;

    recognition.value = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
    recognition.value.lang = 'fr-FR';
    recognition.value.continuous = false;
    recognition.value.interimResults = false;

    recognition.value.onstart = () => {
        isListening.value = true;
    };

    recognition.value.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        inputMessage.value = transcript;
        isListening.value = false;
    };

    recognition.value.onerror = () => {
        isListening.value = false;
    };

    recognition.value.onend = () => {
        isListening.value = false;
    };

    recognition.value.start();
};

// Check for mobile
const checkMobile = () => {
    isMobile.value = window.innerWidth < 640;
};

// Lifecycle
onMounted(async () => {
    // Check speech recognition support
    speechSupported.value = 'SpeechRecognition' in window || 'webkitSpeechRecognition' in window;

    // Check mobile
    checkMobile();
    window.addEventListener('resize', checkMobile);

    // Fetch provider info
    try {
        const response = await axios.get('/api/v1/chatbot/provider');
        if (response.data) {
            aiProvider.value = response.data;
        }
    } catch (e) {
        aiProvider.value = { provider: 'fallback', model: '', has_api_key: false };
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
    recognition.value?.stop();
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.slide-up-enter-from {
    transform: translateY(100%) scale(0.8);
    opacity: 0;
}

.slide-up-leave-to {
    transform: translateY(100%) scale(0.8);
    opacity: 0;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.animate-shimmer {
    animation: shimmer 3s infinite;
}

/* Markdown styles */
.prose :deep(p) {
    margin: 0.5em 0;
}
.prose :deep(ul), .prose :deep(ol) {
    margin: 0.5em 0;
    padding-left: 1.5em;
}
.prose :deep(li) {
    margin: 0.25em 0;
}
.prose :deep(strong) {
    font-weight: 600;
}
.prose :deep(a) {
    color: #2563eb;
    text-decoration: underline;
}
</style>
