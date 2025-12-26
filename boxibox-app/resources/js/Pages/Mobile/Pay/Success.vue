<template>
    <MobileLayout title="Paiement reussi" :show-back="false">
        <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
            <!-- Success Icon -->
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mb-6 animate-bounce-once">
                <CheckCircleIcon class="w-16 h-16 text-green-500" />
            </div>

            <!-- Title -->
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Paiement reussi !</h1>
            <p class="text-gray-600 mb-8">
                Votre paiement a ete traite avec succes. Un email de confirmation vous a ete envoye.
            </p>

            <!-- Receipt Icon -->
            <div class="bg-white rounded-2xl shadow-sm p-6 w-full max-w-sm mb-8">
                <div class="flex items-center justify-center gap-3 text-gray-600">
                    <ReceiptPercentIcon class="w-6 h-6" />
                    <span>Recu disponible dans vos paiements</span>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-3 w-full max-w-sm">
                <Link
                    :href="route('mobile.payments')"
                    class="block w-full py-3.5 bg-primary-600 text-white font-semibold rounded-xl text-center"
                >
                    Voir mes paiements
                </Link>
                <Link
                    :href="route('mobile.dashboard')"
                    class="block w-full py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl text-center"
                >
                    Retour a l'accueil
                </Link>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import { CheckCircleIcon, ReceiptPercentIcon } from '@heroicons/vue/24/outline'
</script>

<style scoped>
@keyframes bounce-once {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-bounce-once {
    animation: bounce-once 0.6s ease-out;
}
</style>
