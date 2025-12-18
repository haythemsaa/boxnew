<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    ticket: Object,
    messages: Array,
})

const messagesContainer = ref(null)

const form = useForm({
    message: '',
})

const sendMessage = () => {
    if (!form.message.trim()) return

    form.post(route('customer.portal.support.message', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            scrollToBottom()
        },
    })
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
        }
    })
}

onMounted(() => {
    scrollToBottom()
})

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-blue-100 text-blue-800',
        pending: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-purple-100 text-purple-800',
        waiting_customer: 'bg-orange-100 text-orange-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <Head :title="`Ticket ${ticket.ticket_number}`" />

    <PortalLayout>
        <div class="h-[calc(100vh-8rem)] flex flex-col max-w-4xl mx-auto px-4 py-6">
            <!-- Header -->
            <div class="bg-white rounded-t-xl border border-b-0 px-4 py-3">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('customer.portal.support.index')"
                        class="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono text-gray-400">{{ ticket.ticket_number }}</span>
                            <span :class="['px-2 py-0.5 text-xs rounded-full', getStatusColor(ticket.status)]">
                                {{ ticket.status_label }}
                            </span>
                        </div>
                        <h1 class="font-semibold text-gray-900 truncate">{{ ticket.subject }}</h1>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div
                ref="messagesContainer"
                class="flex-1 overflow-y-auto bg-gray-50 border-x p-4 space-y-4"
            >
                <!-- Initial Description -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-xs text-blue-600 mb-1">{{ ticket.category_label }} - {{ ticket.created_at }}</div>
                    <p class="text-sm text-gray-700">{{ ticket.description }}</p>
                </div>

                <!-- Messages -->
                <div
                    v-for="message in messages"
                    :key="message.id"
                    :class="[
                        'flex',
                        message.is_mine ? 'justify-end' : 'justify-start',
                        message.sender_type === 'system' && 'justify-center'
                    ]"
                >
                    <!-- System Message -->
                    <div
                        v-if="message.sender_type === 'system'"
                        class="text-xs text-gray-500 bg-gray-200 px-3 py-1 rounded-full"
                    >
                        {{ message.message }}
                    </div>

                    <!-- Regular Message -->
                    <div
                        v-else
                        :class="[
                            'max-w-[80%] rounded-2xl px-4 py-2',
                            message.is_mine
                                ? 'bg-primary-600 text-white rounded-br-md'
                                : 'bg-white border shadow-sm text-gray-900 rounded-bl-md'
                        ]"
                    >
                        <p class="text-sm whitespace-pre-wrap">{{ message.message }}</p>
                        <div :class="[
                            'text-xs mt-1 flex items-center justify-end gap-1',
                            message.is_mine ? 'text-primary-200' : 'text-gray-400'
                        ]">
                            {{ message.created_at }}
                            <svg
                                v-if="message.is_read && message.is_mine"
                                class="w-3 h-3"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Resolved/Closed Notice -->
                <div
                    v-if="ticket.status === 'resolved' || ticket.status === 'closed'"
                    class="text-center"
                >
                    <div class="inline-block bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                        Ce ticket a ete {{ ticket.status === 'resolved' ? 'resolu' : 'ferme' }}.
                        <span v-if="ticket.status === 'resolved'">Vous pouvez repondre pour le rouvrir.</span>
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="bg-white rounded-b-xl border border-t-0 p-4">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <textarea
                        v-model="form.message"
                        @keydown.enter.exact.prevent="sendMessage"
                        placeholder="Ecrivez votre message..."
                        rows="2"
                        :disabled="ticket.status === 'closed'"
                        class="flex-1 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 resize-none disabled:bg-gray-100 disabled:cursor-not-allowed"
                    />
                    <button
                        type="submit"
                        :disabled="form.processing || !form.message.trim() || ticket.status === 'closed'"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition self-end"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
                <p v-if="ticket.status === 'closed'" class="text-xs text-gray-500 mt-2 text-center">
                    Ce ticket est ferme et ne peut plus recevoir de messages.
                </p>
            </div>
        </div>
    </PortalLayout>
</template>
