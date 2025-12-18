<template>
    <div>
        <!-- Chat Button -->
        <button
            v-if="!isOpen"
            @click="openChat"
            class="fixed bottom-6 right-6 w-16 h-16 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 z-50"
        >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
                {{ unreadCount }}
            </span>
        </button>

        <!-- Chat Window -->
        <transition name="slide-up">
            <div
                v-if="isOpen"
                class="fixed bottom-6 right-6 w-96 h-[600px] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden z-50"
            >
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">Assistant Boxibox</h3>
                            <p class="text-xs text-blue-100">En ligne • Répond en < 1 min</p>
                        </div>
                    </div>
                    <button @click="closeChat" class="text-white/80 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Messages -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50">
                    <!-- Welcome Message -->
                    <div v-if="messages.length === 0" class="space-y-3">
                        <div class="flex items-start space-x-2">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-sm max-w-[80%]">
                                <p class="text-sm text-gray-800">
                                    👋 Bonjour! Je suis votre assistant Boxibox.<br><br>
                                    Je peux vous aider à:
                                </p>
                                <ul class="text-sm text-gray-700 mt-2 space-y-1">
                                    <li>📦 Trouver la taille de box idéale</li>
                                    <li>💰 Calculer un devis</li>
                                    <li>📅 Prendre rendez-vous</li>
                                    <li>❓ Répondre à vos questions</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="flex flex-wrap gap-2 ml-10">
                            <button
                                v-for="action in quickActions"
                                :key="action"
                                @click="sendMessage(action)"
                                class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm rounded-full hover:bg-gray-50 transition"
                            >
                                {{ action }}
                            </button>
                        </div>
                    </div>

                    <!-- Message History -->
                    <div
                        v-for="(message, index) in messages"
                        :key="index"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            v-if="message.role === 'assistant'"
                            class="flex items-start space-x-2 max-w-[80%]"
                        >
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-sm">
                                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ message.content }}</p>
                                </div>
                                <!-- Suggestions -->
                                <div v-if="message.suggestions && message.suggestions.length > 0" class="flex flex-wrap gap-2 mt-2">
                                    <button
                                        v-for="(suggestion, idx) in message.suggestions"
                                        :key="idx"
                                        @click="sendMessage(suggestion)"
                                        class="px-3 py-1 bg-gray-100 text-gray-700 text-xs rounded-full hover:bg-gray-200 transition"
                                    >
                                        {{ suggestion }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="bg-blue-600 text-white rounded-2xl rounded-tr-none px-4 py-3 shadow-sm max-w-[80%]"
                        >
                            <p class="text-sm">{{ message.content }}</p>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="flex items-start space-x-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 shadow-sm">
                            <div class="flex space-x-1">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s;"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="p-4 bg-white border-t border-gray-200">
                    <form @submit.prevent="handleSubmit" class="flex items-center space-x-2">
                        <input
                            v-model="inputMessage"
                            type="text"
                            placeholder="Tapez votre message..."
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :disabled="isTyping"
                        />
                        <button
                            type="submit"
                            :disabled="!inputMessage.trim() || isTyping"
                            class="w-10 h-10 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-full flex items-center justify-center transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2 text-center flex items-center justify-center gap-2">
                        <span class="flex items-center gap-1">
                            <span :class="['w-2 h-2 rounded-full', aiProvider.has_api_key ? 'bg-green-400' : 'bg-amber-400']"></span>
                            Propulsé par {{ providerDisplayName }}
                        </span>
                        • 🔒
                    </p>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, nextTick, onMounted, computed } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const messages = ref([]);
const inputMessage = ref('');
const aiProvider = ref({ provider: 'loading', model: '', has_api_key: false });
const isTyping = ref(false);
const unreadCount = ref(0);
const conversationId = ref(null);
const messagesContainer = ref(null);

const quickActions = [
    'Voir les prix',
    'Quelle taille me faut-il?',
    'Prendre rendez-vous',
];

const openChat = () => {
    isOpen.value = true;
    unreadCount.value = 0;
};

const closeChat = () => {
    isOpen.value = false;
};

const handleSubmit = async () => {
    if (!inputMessage.value.trim()) return;

    const userMessage = inputMessage.value;
    inputMessage.value = '';

    // Add user message
    messages.value.push({
        role: 'user',
        content: userMessage,
    });

    scrollToBottom();

    // Show typing indicator
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
            suggestions: response.data.suggestions || [],
        });

    } catch (error) {
        console.error('Chatbot error:', error);
        messages.value.push({
            role: 'assistant',
            content: 'Désolé, une erreur s\'est produite. Veuillez réessayer.',
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

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Provider display name computed
const providerDisplayName = computed(() => {
    const names = {
        groq: 'Groq AI',
        gemini: 'Gemini',
        openrouter: 'OpenRouter',
        openai: 'OpenAI',
        fallback: 'IA locale',
        loading: 'IA...',
    };
    return names[aiProvider.value.provider] || 'IA';
});

// Fetch provider info on mount
onMounted(async () => {
    try {
        const response = await axios.get('/api/v1/chatbot/provider');
        if (response.data) {
            aiProvider.value = response.data;
        }
    } catch (e) {
        // Fallback - keep default
        aiProvider.value = { provider: 'fallback', model: '', has_api_key: false };
    }
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
</style>
