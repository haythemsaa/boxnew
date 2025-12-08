<template>
    <div class="relative" ref="dropdownRef">
        <button
            @click="isOpen = !isOpen"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors"
            :class="buttonClass"
        >
            <span class="text-lg">{{ currentLocaleInfo.flag }}</span>
            <span v-if="showLabel" class="hidden sm:inline">{{ currentLocaleInfo.name }}</span>
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50"
            >
                <button
                    v-for="loc in availableLocales"
                    :key="loc.code"
                    @click="changeLocale(loc.code)"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left hover:bg-gray-50 transition-colors"
                    :class="{ 'bg-indigo-50 text-indigo-700': locale === loc.code }"
                >
                    <span class="text-lg">{{ loc.flag }}</span>
                    <span class="font-medium">{{ loc.name }}</span>
                    <svg
                        v-if="locale === loc.code"
                        class="w-4 h-4 ml-auto text-indigo-600"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useTranslation } from '@/composables/useTranslation'
import { router } from '@inertiajs/vue3'

defineProps({
    showLabel: {
        type: Boolean,
        default: true,
    },
    buttonClass: {
        type: String,
        default: '',
    },
})

const { locale, setLocale, availableLocales, currentLocaleInfo } = useTranslation()

const isOpen = ref(false)
const dropdownRef = ref(null)

const changeLocale = async (newLocale) => {
    // Update local state first
    await setLocale(newLocale)
    isOpen.value = false

    // Post to server to update session/cookie
    router.post(`/locale/${newLocale}`, {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            // Reload the page to apply translations
            window.location.reload()
        },
        onError: () => {
            // Still reload even if server fails (localStorage will persist)
            window.location.reload()
        }
    })
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
