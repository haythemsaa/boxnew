<template>
    <button
        @click="toggleDarkMode"
        class="p-2 rounded-lg transition-all duration-200"
        :class="isDarkMode
            ? 'bg-gray-700 text-yellow-400 hover:bg-gray-600'
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
        :title="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <svg
            v-if="isDarkMode"
            class="w-5 h-5"
            fill="currentColor"
            viewBox="0 0 20 20"
        >
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
        </svg>
        <svg
            v-else
            class="w-5 h-5"
            fill="currentColor"
            viewBox="0 0 20 20"
        >
            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.536l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm5.657-9.193a1 1 0 00-1.414 0l-.707.707A1 1 0 005.05 6.464l.707-.707a1 1 0 011.414-1.414zM5 11a1 1 0 100-2H4a1 1 0 100 2h1z" clip-rule="evenodd"></path>
        </svg>
    </button>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const isDarkMode = ref(false)

onMounted(() => {
    // Check system preference or local storage
    if (localStorage.getItem('theme') === 'dark') {
        isDarkMode.value = true
        document.documentElement.classList.add('dark')
    } else if (localStorage.getItem('theme') === 'light') {
        isDarkMode.value = false
        document.documentElement.classList.remove('dark')
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        isDarkMode.value = true
        document.documentElement.classList.add('dark')
    }
})

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value

    if (isDarkMode.value) {
        document.documentElement.classList.add('dark')
        localStorage.setItem('theme', 'dark')
    } else {
        document.documentElement.classList.remove('dark')
        localStorage.setItem('theme', 'light')
    }
}
</script>
