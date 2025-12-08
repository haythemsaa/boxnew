<template>
    <Link
        :href="href"
        :class="[
            'group relative flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200',
            active
                ? 'text-white shadow-md'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
        ]"
        :style="active ? 'background: linear-gradient(135deg, #8fbd56 0%, #5cd3b9 100%);' : ''"
    >
        <!-- Active Indicator -->
        <div
            v-if="active && !noIndicator"
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 rounded-r-full"
            style="background: linear-gradient(180deg, #8fbd56 0%, #38cab3 100%);"
        ></div>

        <!-- Icon -->
        <div
            :class="[
                'flex items-center justify-center',
                collapsed ? 'mx-auto' : 'mr-3',
                active ? 'text-white' : 'text-gray-400 group-hover:text-gray-600'
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

        <!-- Chevron for expandable items -->
        <transition name="fade">
            <span v-if="!collapsed && hasSubmenu" class="ml-auto">
                <svg
                    :class="['w-4 h-4 transition-transform', expanded ? 'rotate-90' : '']"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
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
    },
    hasSubmenu: {
        type: Boolean,
        default: false
    },
    expanded: {
        type: Boolean,
        default: false
    },
    noIndicator: {
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
