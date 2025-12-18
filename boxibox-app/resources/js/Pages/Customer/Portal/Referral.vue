<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue'

const props = defineProps({
    stats: Object,
    history: Array,
    shareUrl: String,
})

const copied = ref(false)
const copiedUrl = ref(false)

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(props.stats.code)
        copied.value = true
        setTimeout(() => copied.value = false, 2000)
    } catch (e) {
        const input = document.createElement('input')
        input.value = props.stats.code
        document.body.appendChild(input)
        input.select()
        document.execCommand('copy')
        document.body.removeChild(input)
        copied.value = true
        setTimeout(() => copied.value = false, 2000)
    }
}

const copyUrl = async () => {
    try {
        await navigator.clipboard.writeText(props.shareUrl)
        copiedUrl.value = true
        setTimeout(() => copiedUrl.value = false, 2000)
    } catch (e) {
        copiedUrl.value = true
        setTimeout(() => copiedUrl.value = false, 2000)
    }
}

const shareViaWhatsApp = () => {
    const text = `Découvrez BoxiBox pour votre stockage ! Utilisez mon code ${props.stats.code} pour obtenir une réduction. ${props.shareUrl}`
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
}

const shareViaEmail = () => {
    const subject = 'Je te recommande BoxiBox pour ton stockage'
    const body = `Salut,\n\nJe te recommande BoxiBox pour tes besoins de stockage. C'est vraiment pratique !\n\nUtilise mon code de parrainage : ${props.stats.code}\n\nOu clique directement sur ce lien : ${props.shareUrl}\n\nÀ bientôt !`
    window.open(`mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`)
}

const shareViaSMS = () => {
    const text = `Découvre BoxiBox ! Utilise mon code ${props.stats.code} pour une réduction: ${props.shareUrl}`
    window.open(`sms:?body=${encodeURIComponent(text)}`)
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price)
}
</script>

<template>
    <Head title="Programme de parrainage" />

    <CustomerPortalLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Programme de parrainage</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Parrainez vos proches et gagnez des récompenses
                    </p>
                </div>

                <!-- Hero Banner -->
                <div class="bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 rounded-2xl p-8 mb-8 text-white relative overflow-hidden">
                    <!-- Decorative circles -->
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white/10 rounded-full"></div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-3 bg-white/20 rounded-xl">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-purple-200 text-sm">Récompense par parrainage</p>
                                <p class="text-3xl font-bold">{{ formatPrice(stats.reward_per_referral) }}</p>
                            </div>
                        </div>

                        <p class="text-purple-100 mb-6">
                            Pour chaque ami qui loue un box avec votre code, vous recevez {{ formatPrice(stats.reward_per_referral) }} de crédit sur votre prochaine facture !
                        </p>

                        <!-- Referral Code -->
                        <div class="bg-white/20 backdrop-blur rounded-xl p-4">
                            <p class="text-sm text-purple-200 mb-2">Votre code de parrainage</p>
                            <div class="flex items-center gap-3">
                                <code class="flex-1 text-2xl md:text-3xl font-mono font-bold tracking-widest">
                                    {{ stats.code }}
                                </code>
                                <button
                                    @click="copyCode"
                                    :class="[
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                                        copied
                                            ? 'bg-green-500 text-white'
                                            : 'bg-white text-indigo-600 hover:bg-purple-50'
                                    ]"
                                >
                                    <span v-if="!copied" class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Copier
                                    </span>
                                    <span v-else class="flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Copié !
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total parrainages</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_referrals }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Validés</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.successful_referrals }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ formatPrice(stats.pending_reward) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total gagné</p>
                        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatPrice(stats.total_earned) }}</p>
                    </div>
                </div>

                <!-- Share Options -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Partager votre code</h2>
                    </div>
                    <div class="p-6">
                        <!-- Share URL -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lien de parrainage</label>
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    :value="shareUrl"
                                    readonly
                                    class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 text-sm"
                                >
                                <button
                                    @click="copyUrl"
                                    :class="[
                                        'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                                        copiedUrl
                                            ? 'bg-green-500 text-white'
                                            : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                    ]"
                                >
                                    {{ copiedUrl ? 'Copié !' : 'Copier' }}
                                </button>
                            </div>
                        </div>

                        <!-- Share Buttons -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Partager via</label>
                            <div class="flex flex-wrap gap-3">
                                <button
                                    @click="shareViaWhatsApp"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 transition"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    WhatsApp
                                </button>
                                <button
                                    @click="shareViaEmail"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Email
                                </button>
                                <button
                                    @click="shareViaSMS"
                                    class="flex items-center gap-2 px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    SMS
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- How it works -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Comment ça marche ?</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center">
                                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">1</span>
                                </div>
                                <h3 class="font-medium text-gray-900 dark:text-white mb-1">Partagez votre code</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Envoyez votre code de parrainage à vos amis et famille</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center">
                                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400">2</span>
                                </div>
                                <h3 class="font-medium text-gray-900 dark:text-white mb-1">Ils louent un box</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Votre filleul réserve un box en utilisant votre code</p>
                            </div>
                            <div class="text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                                    <span class="text-xl font-bold text-green-600 dark:text-green-400">3</span>
                                </div>
                                <h3 class="font-medium text-gray-900 dark:text-white mb-1">Vous êtes récompensé</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Après 3 mois de location, recevez {{ formatPrice(stats.reward_per_referral) }} de crédit</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referral History -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Historique des parrainages</h2>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="item in history" :key="item.id" class="p-4 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.date }}</p>
                            </div>
                            <span :class="[
                                'px-3 py-1 rounded-full text-sm font-medium',
                                item.status === 'validated' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                item.status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' :
                                'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                            ]">
                                {{ item.status === 'validated' ? 'Validé' : item.status === 'pending' ? 'En cours' : item.status }}
                            </span>
                        </div>

                        <div v-if="history.length === 0" class="p-12 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucun parrainage pour l'instant</h3>
                            <p class="text-gray-500 dark:text-gray-400">Partagez votre code pour commencer à gagner des récompenses !</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>
