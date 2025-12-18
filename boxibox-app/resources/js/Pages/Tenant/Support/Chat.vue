<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    ticket: Object,
    messages: Array,
    statuses: Object,
    priorities: Object,
})

const messagesContainer = ref(null)
const showStatusModal = ref(false)

const form = useForm({
    message: '',
    is_internal: false,
})

const statusForm = useForm({
    status: props.ticket.status,
})

const sendMessage = () => {
    if (!form.message.trim()) return

    form.post(route('tenant.support.message', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
            scrollToBottom()
        },
    })
}

const updateStatus = () => {
    statusForm.put(route('tenant.support.status', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            showStatusModal.value = false
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

const formatTime = (date) => {
    return date
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

    <TenantLayout>
        <div class="h-[calc(100vh-4rem)] flex flex-col">
            <!-- Header -->
            <div class="bg-white border-b px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tenant.support.index')"
                        class="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono text-gray-400">{{ ticket.ticket_number }}</span>
                            <span :class="['px-2 py-0.5 text-xs rounded-full', getStatusColor(ticket.status)]">
                                {{ ticket.status_label }}
                            </span>
                        </div>
                        <h1 class="font-semibold text-gray-900">{{ ticket.subject }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="showStatusModal = true"
                        class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                    >
                        Changer Statut
                    </button>
                </div>
            </div>

            <div class="flex-1 flex overflow-hidden">
                <!-- Messages -->
                <div class="flex-1 flex flex-col">
                    <div
                        ref="messagesContainer"
                        class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50"
                    >
                        <!-- Initial Description -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="text-xs text-blue-600 mb-1">Description initiale</div>
                            <p class="text-sm text-gray-700">{{ ticket.description }}</p>
                        </div>

                        <!-- Messages -->
                        <div
                            v-for="message in messages"
                            :key="message.id"
                            :class="[
                                'flex',
                                message.sender_type === 'user' ? 'justify-end' : 'justify-start',
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
                                    'max-w-[70%] rounded-2xl px-4 py-2',
                                    message.sender_type === 'user'
                                        ? 'bg-primary-600 text-white rounded-br-md'
                                        : 'bg-white border text-gray-900 rounded-bl-md',
                                    message.is_internal && 'border-2 border-yellow-400 bg-yellow-50'
                                ]"
                            >
                                <div class="flex items-center gap-2 mb-1">
                                    <span :class="[
                                        'text-xs font-medium',
                                        message.sender_type === 'user' ? 'text-primary-100' : 'text-gray-500'
                                    ]">
                                        {{ message.sender_name }}
                                    </span>
                                    <span
                                        v-if="message.is_internal"
                                        class="text-xs bg-yellow-200 text-yellow-800 px-1.5 py-0.5 rounded"
                                    >
                                        Interne
                                    </span>
                                </div>
                                <p class="text-sm whitespace-pre-wrap">{{ message.message }}</p>
                                <div :class="[
                                    'text-xs mt-1 flex items-center gap-1',
                                    message.sender_type === 'user' ? 'text-primary-200' : 'text-gray-400'
                                ]">
                                    {{ message.created_at }}
                                    <svg
                                        v-if="message.is_read && message.sender_type === 'user'"
                                        class="w-3 h-3"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input -->
                    <div class="bg-white border-t p-4">
                        <form @submit.prevent="sendMessage" class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input
                                        type="checkbox"
                                        v-model="form.is_internal"
                                        class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-500"
                                    />
                                    Note interne (invisible au client)
                                </label>
                            </div>
                            <div class="flex gap-2">
                                <textarea
                                    v-model="form.message"
                                    @keydown.enter.exact.prevent="sendMessage"
                                    placeholder="Tapez votre message..."
                                    rows="2"
                                    class="flex-1 rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 resize-none"
                                />
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.message.trim()"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar - Customer Info -->
                <div class="w-80 bg-white border-l overflow-y-auto hidden lg:block">
                    <div class="p-4 space-y-4">
                        <h3 class="font-semibold text-gray-900">Informations Client</h3>

                        <div v-if="ticket.customer" class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center font-semibold">
                                    {{ ticket.customer.name?.charAt(0) || 'C' }}
                                </div>
                                <div>
                                    <div class="font-medium">{{ ticket.customer.name }}</div>
                                    <div class="text-sm text-gray-500">{{ ticket.customer.email }}</div>
                                </div>
                            </div>
                            <div v-if="ticket.customer.phone" class="text-sm">
                                <span class="text-gray-500">Tel:</span> {{ ticket.customer.phone }}
                            </div>
                        </div>

                        <hr />

                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-gray-700">Details du Ticket</h4>
                            <div class="text-sm">
                                <span class="text-gray-500">Categorie:</span>
                                <span class="ml-1">{{ ticket.category_label }}</span>
                            </div>
                            <div class="text-sm">
                                <span class="text-gray-500">Priorite:</span>
                                <span class="ml-1">{{ ticket.priority_label }}</span>
                            </div>
                            <div class="text-sm">
                                <span class="text-gray-500">Cree le:</span>
                                <span class="ml-1">{{ ticket.created_at }}</span>
                            </div>
                            <div v-if="ticket.assignee" class="text-sm">
                                <span class="text-gray-500">Assigne a:</span>
                                <span class="ml-1">{{ ticket.assignee.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Modal -->
        <Teleport to="body">
            <div v-if="showStatusModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50" @click="showStatusModal = false" />
                <div class="relative bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
                    <h3 class="text-lg font-semibold mb-4">Changer le Statut</h3>
                    <select
                        v-model="statusForm.status"
                        class="w-full rounded-lg border-gray-300 mb-4"
                    >
                        <option v-for="(label, key) in statuses" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>
                    <div class="flex justify-end gap-2">
                        <button
                            @click="showStatusModal = false"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                        >
                            Annuler
                        </button>
                        <button
                            @click="updateStatus"
                            :disabled="statusForm.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            Confirmer
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>
