<script setup>
defineProps({
    signature: Object,
    signedAt: String,
})
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-emerald-50 to-teal-100 flex items-center justify-center p-4">
        <div class="max-w-md w-full text-center">
            <div class="bg-white rounded-3xl shadow-xl p-8">
                <!-- Icon -->
                <div class="w-20 h-20 bg-emerald-100 rounded-full mx-auto flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-2">Document deja signe</h1>
                <p class="text-gray-600 mb-4">
                    Ce document a deja ete signe le {{ signedAt }}.
                </p>
                <p class="text-sm text-gray-500 mb-6">
                    Une copie du document signe vous a ete envoyee par email.
                </p>

                <div class="space-y-3">
                    <a
                        href="/portal/dashboard"
                        class="inline-flex items-center justify-center w-full py-3 px-6 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-colors"
                    >
                        Acceder a mon espace client
                    </a>
                    <a
                        href="/"
                        class="inline-flex items-center justify-center w-full py-3 px-6 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition-colors"
                    >
                        Retour a l'accueil
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
