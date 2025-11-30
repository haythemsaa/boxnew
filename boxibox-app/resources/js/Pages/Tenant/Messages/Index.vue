<template>
    <AuthenticatedLayout title="Messages">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Messages</h1>
                    <p class="mt-1 text-sm text-gray-500">Gérez vos communications avec les clients</p>
                </div>
                <button
                    @click="showComposeModal = true"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouveau message
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Non lus</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.unread }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Envoyés</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.sent }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">SMS</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ stats.sms }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            type="text"
                            v-model="filters.search"
                            placeholder="Rechercher..."
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                    </div>
                    <select
                        v-model="filters.type"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Tous les types</option>
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                    </select>
                    <select
                        v-model="filters.status"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="sent">Envoyé</option>
                        <option value="pending">En attente</option>
                        <option value="failed">Échoué</option>
                    </select>
                </div>
            </div>

            <!-- Messages List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="divide-y divide-gray-200">
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="p-4 hover:bg-gray-50 cursor-pointer"
                        :class="{ 'bg-blue-50': !message.read }"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <span
                                        :class="[
                                            'inline-flex items-center justify-center w-10 h-10 rounded-full',
                                            message.type === 'email' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'
                                        ]"
                                    >
                                        <svg v-if="message.type === 'email'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900" :class="{ 'font-bold': !message.read }">
                                        {{ message.recipient }}
                                    </p>
                                    <p class="text-sm text-gray-600 truncate">{{ message.subject }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ message.preview }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-xs text-gray-500">{{ message.sent_at }}</span>
                                <span
                                    :class="[
                                        'mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                        message.status === 'sent' ? 'bg-green-100 text-green-800' :
                                        message.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-red-100 text-red-800'
                                    ]"
                                >
                                    {{ message.status === 'sent' ? 'Envoyé' : message.status === 'pending' ? 'En attente' : 'Échoué' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="messages.length === 0" class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun message</h3>
                        <p class="mt-1 text-sm text-gray-500">Commencez par envoyer un message à vos clients.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compose Modal -->
        <div v-if="showComposeModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showComposeModal = false"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Nouveau message</h3>
                    <form @submit.prevent="sendMessage">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <select v-model="newMessage.type" class="mt-1 block w-full rounded-lg border-gray-300">
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Destinataire</label>
                                <input type="text" v-model="newMessage.recipient" class="mt-1 block w-full rounded-lg border-gray-300" placeholder="Email ou téléphone" />
                            </div>
                            <div v-if="newMessage.type === 'email'">
                                <label class="block text-sm font-medium text-gray-700">Sujet</label>
                                <input type="text" v-model="newMessage.subject" class="mt-1 block w-full rounded-lg border-gray-300" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Message</label>
                                <textarea v-model="newMessage.content" rows="4" class="mt-1 block w-full rounded-lg border-gray-300"></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" @click="showComposeModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Annuler
                            </button>
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                                Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

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
        preview: 'Bonjour, nous vous rappelons que votre facture du...',
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
        preview: 'Votre réservation a bien été confirmée pour le...',
        status: 'pending',
        sent_at: 'Hier',
        read: true
    }
])

const sendMessage = () => {
    // TODO: Implement send message
    showComposeModal.value = false
}
</script>
