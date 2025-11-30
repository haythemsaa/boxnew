<script setup>
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div class="max-w-md w-full text-center">
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <!-- Icon -->
                <div class="w-20 h-20 bg-red-100 rounded-full mx-auto flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Lien invalide</h1>
                <p class="text-gray-600 mb-6">
                    Ce lien de signature n'existe pas ou n'est plus valide. Veuillez verifier le lien ou contacter votre gestionnaire.
                </p>

                <a
                    href="/"
                    class="inline-flex items-center justify-center w-full py-3 px-6 bg-gray-900 text-white font-semibold rounded-xl hover:bg-gray-800 transition-colors"
                >
                    Retour a l'accueil
                </a>
            </div>
        </div>
    </div>
</template>
