<script setup>
defineProps({
    signature: Object,
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div class="max-w-md w-full text-center">
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <!-- Icon -->
                <div class="w-20 h-20 bg-gray-100 rounded-full mx-auto flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Signature refusee</h1>
                <p class="text-gray-600 mb-6">
                    Cette demande de signature a ete refusee. Si vous avez change d'avis, veuillez contacter votre gestionnaire pour obtenir un nouveau lien.
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
