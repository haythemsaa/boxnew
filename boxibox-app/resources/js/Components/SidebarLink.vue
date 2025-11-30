<template>
    <Link
        :href="href"
        :class="[
            'group relative flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
            active
                ? 'bg-white/15 text-white shadow-lg shadow-white/5'
                : 'text-gray-300 hover:bg-white/10 hover:text-white'
        ]"
    >
        <!-- Active Indicator -->
        <div
            v-if="active"
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-primary-400 rounded-r-full"
        ></div>

        <!-- Icon -->
        <div
            :class="[
                'flex items-center justify-center',
                collapsed ? 'mx-auto' : 'mr-3',
                active ? 'text-primary-400' : 'text-gray-400 group-hover:text-white'
            ]"
        >
            <slot name="icon" />
        </div>

        <!-- Label -->
        <transition name="fade-slide">
            <span v-if="!collapsed" class="flex-1 truncate">
                <slot />
            </span>
        </transition>

        <!-- Badge -->
        <transition name="fade">
            <span v-if="!collapsed">
                <slot name="badge" />
            </span>
        </transition>

        <!-- Tooltip for collapsed state -->
        <div
            v-if="collapsed"
            class="absolute left-full ml-3 px-3 py-1.5 bg-gray-900 text-white text-sm rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 shadow-xl"
        >
            <slot />
            <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1 w-2 h-2 bg-gray-900 rotate-45"></div>
        </div>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    href: {
        type: String,
        required: true
    },
    active: {
        type: Boolean,
        default: false
    },
    collapsed: {
        type: Boolean,
        default: false
    }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.2s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}
</style>
