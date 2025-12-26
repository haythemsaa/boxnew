<template>
    <MobileLayout title="Serrure Connectee" :show-back="true">
        <!-- Lock Status Card -->
        <div
            class="rounded-3xl p-6 mb-6 shadow-2xl relative overflow-hidden"
            :class="lockStatus === 'unlocked'
                ? 'bg-gradient-to-br from-green-500 via-green-600 to-emerald-700'
                : 'bg-gradient-to-br from-slate-700 via-slate-800 to-slate-900'"
        >
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full translate-y-1/2 -translate-x-1/2 blur-2xl"></div>
            </div>

            <div class="relative">
                <!-- Box Info -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-white/70 text-sm font-medium">Mon Box</p>
                        <h2 class="text-2xl font-bold text-white">{{ contract?.box?.name || 'Box A-12' }}</h2>
                        <p class="text-white/60 text-sm flex items-center mt-1">
                            <MapPinIcon class="w-4 h-4 mr-1" />
                            {{ contract?.box?.site?.name || 'Site Paris Centre' }}
                        </p>
                    </div>
                    <div
                        class="w-16 h-16 rounded-2xl flex items-center justify-center"
                        :class="lockStatus === 'unlocked' ? 'bg-white/20' : 'bg-white/10'"
                    >
                        <LockOpenIcon v-if="lockStatus === 'unlocked'" class="w-8 h-8 text-white" />
                        <LockClosedIcon v-else class="w-8 h-8 text-white" />
                    </div>
                </div>

                <!-- Lock Animation -->
                <div class="flex justify-center my-8">
                    <div class="relative">
                        <!-- Pulse rings -->
                        <div
                            v-if="isUnlocking"
                            class="absolute inset-0 flex items-center justify-center"
                        >
                            <div class="absolute w-32 h-32 bg-white/20 rounded-full animate-ping"></div>
                            <div class="absolute w-40 h-40 bg-white/10 rounded-full animate-ping" style="animation-delay: 0.5s"></div>
                        </div>

                        <!-- Main button -->
                        <button
                            @click="toggleLock"
                            :disabled="isUnlocking"
                            class="relative w-32 h-32 rounded-full flex items-center justify-center transition-all duration-500 active:scale-95"
                            :class="[
                                lockStatus === 'unlocked'
                                    ? 'bg-white shadow-xl shadow-white/30'
                                    : 'bg-white/10 border-2 border-white/30 hover:bg-white/20',
                                isUnlocking ? 'animate-pulse' : ''
                            ]"
                        >
                            <template v-if="isUnlocking">
                                <ArrowPathIcon class="w-12 h-12 animate-spin" :class="lockStatus === 'unlocked' ? 'text-green-600' : 'text-white'" />
                            </template>
                            <template v-else>
                                <LockOpenIcon v-if="lockStatus === 'unlocked'" class="w-12 h-12 text-green-600" />
                                <LockClosedIcon v-else class="w-12 h-12 text-white" />
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Status Text -->
                <div class="text-center">
                    <p class="text-white font-bold text-xl mb-1">
                        {{ isUnlocking ? 'En cours...' : (lockStatus === 'unlocked' ? 'Deverrouille' : 'Verrouille') }}
                    </p>
                    <p class="text-white/60 text-sm">
                        {{ isUnlocking
                            ? 'Communication avec la serrure...'
                            : (lockStatus === 'unlocked'
                                ? 'Appuyez pour verrouiller'
                                : 'Appuyez pour deverrouiller')
                        }}
                    </p>
                </div>

                <!-- Auto-lock timer -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div v-if="lockStatus === 'unlocked' && autoLockCountdown > 0" class="mt-4 text-center">
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-full">
                            <ClockIcon class="w-4 h-4 text-white" />
                            <span class="text-white text-sm font-medium">
                                Verrouillage auto dans {{ autoLockCountdown }}s
                            </span>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <button
                @click="unlockTemporary(5)"
                :disabled="isUnlocking"
                class="bg-white rounded-2xl p-4 shadow-lg shadow-gray-200/50 border border-gray-100 hover:shadow-xl active:scale-95 transition-all disabled:opacity-50"
            >
                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center mb-3 shadow-lg shadow-blue-500/30">
                    <ClockIcon class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900">5 minutes</p>
                <p class="text-xs text-gray-500">Ouverture temporaire</p>
            </button>

            <button
                @click="unlockTemporary(15)"
                :disabled="isUnlocking"
                class="bg-white rounded-2xl p-4 shadow-lg shadow-gray-200/50 border border-gray-100 hover:shadow-xl active:scale-95 transition-all disabled:opacity-50"
            >
                <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center mb-3 shadow-lg shadow-purple-500/30">
                    <ClockIcon class="w-6 h-6 text-white" />
                </div>
                <p class="font-semibold text-gray-900">15 minutes</p>
                <p class="text-xs text-gray-500">Ouverture temporaire</p>
            </button>
        </div>

        <!-- Lock Info -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations serrure</h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-3">
                            <WifiIcon class="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Connexion</p>
                            <p class="text-sm text-gray-500">{{ lockProvider }}</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                        En ligne
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mr-3">
                            <Battery50Icon class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Batterie</p>
                            <p class="text-sm text-gray-500">Niveau de charge</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden mr-2">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="batteryLevel > 50 ? 'bg-green-500' : batteryLevel > 20 ? 'bg-yellow-500' : 'bg-red-500'"
                                :style="{ width: batteryLevel + '%' }"
                            ></div>
                        </div>
                        <span class="text-sm font-medium" :class="batteryLevel > 50 ? 'text-green-600' : batteryLevel > 20 ? 'text-yellow-600' : 'text-red-600'">
                            {{ batteryLevel }}%
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mr-3">
                            <ClockIcon class="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Derniere activite</p>
                            <p class="text-sm text-gray-500">{{ lastActivity }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Activite recente</h3>
                <Link :href="route('mobile.access')" class="text-sm text-primary-600 font-medium">
                    Voir tout
                </Link>
            </div>

            <div class="space-y-3">
                <div
                    v-for="activity in recentActivity"
                    :key="activity.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                >
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center mr-3"
                            :class="activity.action === 'unlock' ? 'bg-green-100' : 'bg-blue-100'"
                        >
                            <LockOpenIcon v-if="activity.action === 'unlock'" class="w-5 h-5 text-green-600" />
                            <LockClosedIcon v-else class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ activity.action === 'unlock' ? 'Deverrouillage' : 'Verrouillage' }}
                            </p>
                            <p class="text-sm text-gray-500">{{ activity.method }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">{{ activity.time }}</p>
                </div>
            </div>
        </div>

        <!-- Share Access -->
        <Link
            :href="route('mobile.share-access')"
            class="block bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl p-5 text-white shadow-lg mb-4"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-lg">Partager l'acces</h4>
                    <p class="text-orange-100 text-sm mt-1">Invitez quelqu'un temporairement</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <ShareIcon class="w-6 h-6" />
                </div>
            </div>
        </Link>

        <!-- Help -->
        <div class="bg-amber-50 rounded-2xl p-4">
            <div class="flex items-start">
                <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 mr-3 mt-0.5 flex-shrink-0" />
                <div>
                    <p class="font-medium text-amber-800">Probleme avec la serrure ?</p>
                    <p class="text-sm text-amber-700 mt-1">
                        Si la serrure ne repond pas, verifiez votre connexion internet ou contactez le support.
                    </p>
                    <Link :href="route('mobile.support')" class="text-sm font-medium text-amber-800 underline mt-2 inline-block">
                        Contacter le support
                    </Link>
                </div>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    LockClosedIcon,
    LockOpenIcon,
    MapPinIcon,
    ClockIcon,
    WifiIcon,
    Battery50Icon,
    ShareIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
    lock: Object,
})

const lockStatus = ref('locked') // 'locked' | 'unlocked'
const isUnlocking = ref(false)
const batteryLevel = ref(85)
const autoLockCountdown = ref(0)
const lockProvider = ref('Noke Pro')
const lastActivity = ref('Il y a 2 heures')

let autoLockTimer = null
let countdownInterval = null

const recentActivity = ref([
    { id: 1, action: 'lock', method: 'Application mobile', time: 'Il y a 2h' },
    { id: 2, action: 'unlock', method: 'Application mobile', time: 'Il y a 2h' },
    { id: 3, action: 'lock', method: 'Automatique', time: 'Hier 18:45' },
    { id: 4, action: 'unlock', method: 'Code PIN', time: 'Hier 18:30' },
])

const toggleLock = async () => {
    if (isUnlocking.value) return

    isUnlocking.value = true

    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000))

    if (lockStatus.value === 'locked') {
        lockStatus.value = 'unlocked'
        startAutoLockTimer(30) // Auto-lock after 30 seconds
        // Haptic feedback
        if (navigator.vibrate) {
            navigator.vibrate([50, 50, 50])
        }
    } else {
        lockStatus.value = 'locked'
        clearAutoLockTimer()
        if (navigator.vibrate) {
            navigator.vibrate(100)
        }
    }

    isUnlocking.value = false

    // Add to activity
    recentActivity.value.unshift({
        id: Date.now(),
        action: lockStatus.value === 'unlocked' ? 'unlock' : 'lock',
        method: 'Application mobile',
        time: 'A l\'instant'
    })
}

const unlockTemporary = async (minutes) => {
    if (isUnlocking.value) return

    isUnlocking.value = true
    await new Promise(resolve => setTimeout(resolve, 2000))

    lockStatus.value = 'unlocked'
    startAutoLockTimer(minutes * 60)

    if (navigator.vibrate) {
        navigator.vibrate([50, 50, 50])
    }

    isUnlocking.value = false

    recentActivity.value.unshift({
        id: Date.now(),
        action: 'unlock',
        method: `Temporaire ${minutes} min`,
        time: 'A l\'instant'
    })
}

const startAutoLockTimer = (seconds) => {
    clearAutoLockTimer()
    autoLockCountdown.value = seconds

    countdownInterval = setInterval(() => {
        autoLockCountdown.value--
        if (autoLockCountdown.value <= 0) {
            lockStatus.value = 'locked'
            clearAutoLockTimer()
            recentActivity.value.unshift({
                id: Date.now(),
                action: 'lock',
                method: 'Automatique',
                time: 'A l\'instant'
            })
        }
    }, 1000)
}

const clearAutoLockTimer = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval)
        countdownInterval = null
    }
    autoLockCountdown.value = 0
}

onUnmounted(() => {
    clearAutoLockTimer()
})
</script>
