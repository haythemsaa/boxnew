<template>
    <TenantLayout title="Messages">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-cyan-600 via-teal-600 to-cyan-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                            <ChatBubbleLeftRightIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Messages</h1>
                            <p class="text-cyan-100 mt-1">Gérez vos communications avec les clients</p>
                        </div>
                    </div>
                    <div class="mt-4 lg:mt-0">
                        <button
                            @click="showComposeModal = true"
                            class="px-6 py-3 bg-white text-cyan-700 font-semibold rounded-xl hover:bg-cyan-50 transition-all duration-200 shadow-lg flex items-center space-x-2"
                        >
                            <PlusIcon class="w-5 h-5" />
                            <span>Nouveau message</span>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-cyan-200 text-sm">Total Messages</p>
                                <p class="text-3xl font-bold text-white mt-1">{{ stats.total }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <EnvelopeIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-cyan-200 text-sm">Non lus</p>
                                <p class="text-3xl font-bold text-amber-300 mt-1">{{ stats.unread }}</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-400/30 rounded-xl flex items-center justify-center">
                                <InboxIcon class="w-6 h-6 text-amber-300" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-cyan-200 text-sm">Envoyés</p>
                                <p class="text-3xl font-bold text-emerald-300 mt-1">{{ stats.sent }}</p>
                            </div>
                            <div class="w-12 h-12 bg-emerald-400/30 rounded-xl flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-emerald-300" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-cyan-200 text-sm">SMS</p>
                                <p class="text-3xl font-bold text-purple-300 mt-1">{{ stats.sms }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-400/30 rounded-xl flex items-center justify-center">
                                <DevicePhoneMobileIcon class="w-6 h-6 text-purple-300" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="p-6">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[250px]">
                            <div class="relative">
                                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" />
                                <input
                                    type="text"
                                    v-model="filters.search"
                                    placeholder="Rechercher un message..."
                                    class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all duration-200"
                                />
                            </div>
                        </div>
                        <select
                            v-model="filters.type"
                            class="px-4 py-3 rounded-xl border border-gray-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all duration-200"
                        >
                            <option value="">Tous les types</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                        </select>
                        <select
                            v-model="filters.status"
                            class="px-4 py-3 rounded-xl border border-gray-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all duration-200"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="sent">Envoyé</option>
                            <option value="pending">En attente</option>
                            <option value="failed">Échoué</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Messages List -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="message in filteredMessages"
                        :key="message.id"
                        class="p-5 hover:bg-gray-50 cursor-pointer transition-colors duration-200"
                        :class="{ 'bg-cyan-50/50': !message.read }"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div
                                        :class="[
                                            'w-12 h-12 rounded-xl flex items-center justify-center',
                                            message.type === 'email' ? 'bg-blue-100' : 'bg-emerald-100'
                                        ]"
                                    >
                                        <EnvelopeIcon v-if="message.type === 'email'" class="w-6 h-6 text-blue-600" />
                                        <DevicePhoneMobileIcon v-else class="w-6 h-6 text-emerald-600" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <p class="font-semibold text-gray-900" :class="{ 'font-bold': !message.read }">
                                            {{ message.recipient }}
                                        </p>
                                        <span v-if="!message.read" class="w-2 h-2 bg-cyan-500 rounded-full"></span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700 mt-1">{{ message.subject }}</p>
                                    <p class="text-sm text-gray-500 mt-1 line-clamp-1">{{ message.preview }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end ml-4">
                                <span class="text-xs text-gray-500">{{ message.sent_at }}</span>
                                <span
                                    :class="[
                                        'mt-2 px-2.5 py-1 rounded-lg text-xs font-semibold border',
                                        statusConfig[message.status].color
                                    ]"
                                >
                                    {{ statusConfig[message.status].label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredMessages.length === 0" class="p-12 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <EnvelopeIcon class="w-10 h-10 text-gray-400" />
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Aucun message</h3>
                        <p class="mt-2 text-gray-500">Commencez par envoyer un message à vos clients.</p>
                        <button
                            @click="showComposeModal = true"
                            class="mt-6 px-6 py-3 bg-gradient-to-r from-cyan-600 to-teal-600 text-white font-semibold rounded-xl hover:from-cyan-700 hover:to-teal-700 transition-all duration-200 inline-flex items-center space-x-2"
                        >
                            <PlusIcon class="w-5 h-5" />
                            <span>Nouveau message</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compose Modal -->
        <div v-if="showComposeModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showComposeModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full transform transition-all">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-xl flex items-center justify-center">
                                <PaperAirplaneIcon class="w-5 h-5 text-white" />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Nouveau message</h3>
                        </div>
                        <button @click="showComposeModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="sendMessage" class="p-6">
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type de message</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        type="button"
                                        @click="newMessage.type = 'email'"
                                        :class="[
                                            'p-4 rounded-xl border-2 flex flex-col items-center space-y-2 transition-all duration-200',
                                            newMessage.type === 'email'
                                                ? 'border-cyan-500 bg-cyan-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <EnvelopeIcon class="w-6 h-6" :class="newMessage.type === 'email' ? 'text-cyan-600' : 'text-gray-400'" />
                                        <span class="text-sm font-medium" :class="newMessage.type === 'email' ? 'text-cyan-700' : 'text-gray-600'">Email</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="newMessage.type = 'sms'"
                                        :class="[
                                            'p-4 rounded-xl border-2 flex flex-col items-center space-y-2 transition-all duration-200',
                                            newMessage.type === 'sms'
                                                ? 'border-emerald-500 bg-emerald-50'
                                                : 'border-gray-200 hover:border-gray-300'
                                        ]"
                                    >
                                        <DevicePhoneMobileIcon class="w-6 h-6" :class="newMessage.type === 'sms' ? 'text-emerald-600' : 'text-gray-400'" />
                                        <span class="text-sm font-medium" :class="newMessage.type === 'sms' ? 'text-emerald-700' : 'text-gray-600'">SMS</span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Destinataire</label>
                                <input
                                    type="text"
                                    v-model="newMessage.recipient"
                                    :placeholder="newMessage.type === 'email' ? 'email@exemple.com' : '+33 6 00 00 00 00'"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all duration-200"
                                />
                            </div>

                            <div v-if="newMessage.type === 'email'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sujet</label>
                                <input
                                    type="text"
                                    v-model="newMessage.subject"
                                    placeholder="Objet du message"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all duration-200"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                <textarea
                                    v-model="newMessage.content"
                                    rows="4"
                                    placeholder="Écrivez votre message ici..."
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all duration-200 resize-none"
                                ></textarea>
                                <p v-if="newMessage.type === 'sms'" class="mt-2 text-xs text-gray-500">
                                    {{ newMessage.content.length }} / 160 caractères
                                </p>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="showComposeModal = false"
                                class="px-5 py-2.5 border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors duration-200"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-teal-600 text-white font-semibold rounded-xl hover:from-cyan-700 hover:to-teal-700 transition-all duration-200 flex items-center space-x-2"
                            >
                                <PaperAirplaneIcon class="w-5 h-5" />
                                <span>Envoyer</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ChatBubbleLeftRightIcon,
    PlusIcon,
    EnvelopeIcon,
    InboxIcon,
    CheckCircleIcon,
    DevicePhoneMobileIcon,
    MagnifyingGlassIcon,
    PaperAirplaneIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

const showComposeModal = ref(false)

const stats = reactive({
    total: 156,
    unread: 12,
    sent: 144,
    sms: 45
})

const filters = reactive({
    search: '',
    type: '',
    status: ''
})

const statusConfig = {
    sent: { label: 'Envoyé', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    pending: { label: 'En attente', color: 'bg-amber-100 text-amber-700 border-amber-200' },
    failed: { label: 'Échoué', color: 'bg-red-100 text-red-700 border-red-200' },
}

const newMessage = reactive({
    type: 'email',
    recipient: '',
    subject: '',
    content: ''
})

const messages = ref([
    {
        id: 1,
        type: 'email',
        recipient: 'jean.dupont@email.com',
        subject: 'Rappel de paiement',
        preview: 'Bonjour, nous vous rappelons que votre facture du mois dernier est en attente de paiement...',
        status: 'sent',
        sent_at: 'Il y a 2h',
        read: true
    },
    {
        id: 2,
        type: 'sms',
        recipient: '+33 6 12 34 56 78',
        subject: 'SMS',
        preview: 'Votre box est prêt. Code accès: 1234',
        status: 'sent',
        sent_at: 'Il y a 5h',
        read: false
    },
    {
        id: 3,
        type: 'email',
        recipient: 'marie.martin@email.com',
        subject: 'Confirmation de réservation',
        preview: 'Votre réservation a bien été confirmée pour le box A12 à partir du 1er janvier...',
        status: 'pending',
        sent_at: 'Hier',
        read: true
    }
])

const filteredMessages = computed(() => {
    return messages.value.filter(message => {
        const matchesSearch = !filters.search ||
            message.recipient.toLowerCase().includes(filters.search.toLowerCase()) ||
            message.subject.toLowerCase().includes(filters.search.toLowerCase()) ||
            message.preview.toLowerCase().includes(filters.search.toLowerCase())
        const matchesType = !filters.type || message.type === filters.type
        const matchesStatus = !filters.status || message.status === filters.status
        return matchesSearch && matchesType && matchesStatus
    })
})

const sendMessage = () => {
    // TODO: Implement send message
    showComposeModal.value = false
    newMessage.recipient = ''
    newMessage.subject = ''
    newMessage.content = ''
}
</script>
