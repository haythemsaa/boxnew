<template>
    <MobileLayout title="Support" :show-back="true">
        <!-- Contact Options -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Nous contacter</h3>

            <div class="space-y-3">
                <a
                    href="tel:0800123456"
                    class="flex items-center p-4 bg-green-50 rounded-xl hover:bg-green-100"
                >
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <PhoneIcon class="w-6 h-6 text-green-600" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">Telephone</p>
                        <p class="text-sm text-gray-500">0 800 123 456 (gratuit)</p>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>

                <a
                    href="mailto:support@boxibox.fr"
                    class="flex items-center p-4 bg-blue-50 rounded-xl hover:bg-blue-100"
                >
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                        <EnvelopeIcon class="w-6 h-6 text-blue-600" />
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">Email</p>
                        <p class="text-sm text-gray-500">support@boxibox.fr</p>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>

                <button
                    @click="openChat"
                    class="w-full flex items-center p-4 bg-purple-50 rounded-xl hover:bg-purple-100"
                >
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                        <ChatBubbleLeftRightIcon class="w-6 h-6 text-purple-600" />
                    </div>
                    <div class="flex-1 text-left">
                        <p class="font-semibold text-gray-900">Chat en direct</p>
                        <p class="text-sm text-gray-500">Reponse en moins de 5 min</p>
                    </div>
                    <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full">En ligne</span>
                </button>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Envoyer un message</h3>

            <form @submit.prevent="sendMessage" class="space-y-4">
                <!-- Subject -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sujet</label>
                    <select
                        v-model="form.subject"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 bg-white"
                    >
                        <option value="">Selectionnez un sujet</option>
                        <option value="billing">Facturation</option>
                        <option value="contract">Contrat</option>
                        <option value="access">Probleme d'acces</option>
                        <option value="technical">Probleme technique</option>
                        <option value="complaint">Reclamation</option>
                        <option value="other">Autre</option>
                    </select>
                </div>

                <!-- Related Contract -->
                <div v-if="contracts?.length > 0">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contrat concerne (optionnel)</label>
                    <select
                        v-model="form.contract_id"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 bg-white"
                    >
                        <option value="">Aucun</option>
                        <option v-for="contract in contracts" :key="contract.id" :value="contract.id">
                            {{ contract.box?.name }} - {{ contract.contract_number }}
                        </option>
                    </select>
                </div>

                <!-- Message -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <textarea
                        v-model="form.message"
                        rows="5"
                        placeholder="Decrivez votre demande..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 resize-none"
                    ></textarea>
                </div>

                <!-- Attachment -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Piece jointe (optionnel)</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4">
                        <input
                            type="file"
                            ref="fileInput"
                            @change="handleFileChange"
                            class="hidden"
                            accept="image/*,.pdf"
                        />
                        <button
                            type="button"
                            @click="$refs.fileInput.click()"
                            class="w-full flex flex-col items-center text-gray-500"
                        >
                            <PaperClipIcon class="w-8 h-8 mb-2" />
                            <span class="text-sm">{{ attachment ? attachment.name : 'Ajouter un fichier' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="sending || !form.subject || !form.message"
                    class="w-full py-3.5 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50"
                >
                    {{ sending ? 'Envoi en cours...' : 'Envoyer' }}
                </button>
            </form>
        </div>

        <!-- Recent Tickets -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Mes demandes</h3>

            <div v-if="tickets?.length > 0" class="space-y-3">
                <div
                    v-for="ticket in tickets"
                    :key="ticket.id"
                    class="p-4 bg-gray-50 rounded-xl"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-medium text-gray-900">{{ ticket.subject }}</p>
                            <p class="text-sm text-gray-500">{{ formatDate(ticket.created_at) }}</p>
                        </div>
                        <span
                            class="px-2 py-1 text-xs rounded-full"
                            :class="getStatusClass(ticket.status)"
                        >
                            {{ getStatusLabel(ticket.status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 line-clamp-2">{{ ticket.message }}</p>
                </div>
            </div>
            <div v-else class="text-center py-6 text-gray-500">
                <ChatBubbleLeftRightIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                <p>Aucune demande en cours</p>
            </div>
        </div>

        <!-- Opening Hours -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Horaires du support</h3>

            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Lundi - Vendredi</span>
                    <span class="font-medium text-gray-900">8h00 - 20h00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Samedi</span>
                    <span class="font-medium text-gray-900">9h00 - 18h00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Dimanche</span>
                    <span class="font-medium text-gray-900">Ferme</span>
                </div>
            </div>

            <div class="mt-4 p-3 bg-yellow-50 rounded-xl">
                <p class="text-sm text-yellow-700">
                    En dehors des horaires, laissez un message et nous vous recontacterons des l'ouverture.
                </p>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    PhoneIcon,
    EnvelopeIcon,
    ChatBubbleLeftRightIcon,
    ChevronRightIcon,
    PaperClipIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contracts: Array,
    tickets: Array,
})

const form = ref({
    subject: '',
    contract_id: '',
    message: '',
})

const attachment = ref(null)
const sending = ref(false)

const handleFileChange = (event) => {
    attachment.value = event.target.files[0]
}

const sendMessage = () => {
    sending.value = true

    const formData = new FormData()
    formData.append('subject', form.value.subject)
    formData.append('message', form.value.message)
    if (form.value.contract_id) {
        formData.append('contract_id', form.value.contract_id)
    }
    if (attachment.value) {
        formData.append('attachment', attachment.value)
    }

    router.post(route('mobile.support.send'), formData, {
        onFinish: () => {
            sending.value = false
            form.value = { subject: '', contract_id: '', message: '' }
            attachment.value = null
        },
    })
}

const openChat = () => {
    // Open chat widget
    alert('Chat en direct - Fonctionnalite a venir')
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const getStatusLabel = (status) => {
    const labels = {
        open: 'Ouvert',
        pending: 'En attente',
        resolved: 'Resolu',
        closed: 'Ferme',
    }
    return labels[status] || status
}

const getStatusClass = (status) => {
    const classes = {
        open: 'bg-blue-100 text-blue-700',
        pending: 'bg-yellow-100 text-yellow-700',
        resolved: 'bg-green-100 text-green-700',
        closed: 'bg-gray-100 text-gray-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}
</script>
