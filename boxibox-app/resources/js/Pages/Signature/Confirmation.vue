<script setup>
const props = defineProps({
    signature: Object,
    contract: Object,
    customer: Object,
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100 flex items-center justify-center p-4">
        <div class="max-w-lg w-full">
            <!-- Success Card -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <!-- Header with animation -->
                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-8 py-12 text-center relative overflow-hidden">
                    <!-- Animated circles -->
                    <div class="absolute inset-0">
                        <div class="absolute top-0 left-0 w-40 h-40 bg-white/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                        <div class="absolute bottom-0 right-0 w-32 h-32 bg-white/10 rounded-full translate-x-1/2 translate-y-1/2"></div>
                    </div>

                    <!-- Success icon -->
                    <div class="relative">
                        <div class="w-24 h-24 bg-white rounded-full mx-auto flex items-center justify-center shadow-lg animate-bounce-once">
                            <svg class="w-14 h-14 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold text-white mt-6">Contrat signe avec succes !</h1>
                        <p class="text-emerald-100 mt-2">Merci pour votre confiance</p>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-8 py-8">
                    <!-- Contract info -->
                    <div class="bg-gray-50 rounded-2xl p-5 mb-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Details du contrat</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Numero de contrat</span>
                                <span class="font-semibold text-gray-900">{{ contract?.contract_number }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Box</span>
                                <span class="font-medium text-gray-900">{{ contract?.box?.name }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Signe le</span>
                                <span class="font-medium text-gray-900">{{ formatDate(signature?.signed_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- What's next -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Prochaines etapes</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Confirmation par email</p>
                                    <p class="text-sm text-gray-500">Vous recevrez une copie du contrat signe par email</p>
                                </div>
                            </li>
                            <li class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mt-0.5">
                                    <span class="text-xs font-bold">2</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Acces a votre box</p>
                                    <p class="text-sm text-gray-500">Votre code d'acces vous sera communique sous 24h</p>
                                </div>
                            </li>
                            <li class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-6 h-6 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mt-0.5">
                                    <span class="text-xs font-bold">3</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Espace client</p>
                                    <p class="text-sm text-gray-500">Connectez-vous pour gerer votre location</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Action buttons -->
                    <div class="space-y-3">
                        <a
                            href="/portal/dashboard"
                            class="w-full py-3 px-6 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold rounded-xl hover:from-emerald-600 hover:to-teal-600 transition-all flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Acceder a mon espace client
                        </a>
                        <a
                            href="/"
                            class="w-full py-3 px-6 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors flex items-center justify-center"
                        >
                            Retour a l'accueil
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-100">
                    <p class="text-xs text-gray-500 text-center">
                        Un email de confirmation a ete envoye a {{ customer?.email }}.
                        Si vous ne le recevez pas, verifiez votre dossier spam.
                    </p>
                </div>
            </div>

            <!-- Contact info -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    Besoin d'aide ?
                    <a href="mailto:support@boxibox.fr" class="text-emerald-600 hover:underline">Contactez-nous</a>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce-once {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.animate-bounce-once {
    animation: bounce-once 0.6s ease-out;
}
</style>
