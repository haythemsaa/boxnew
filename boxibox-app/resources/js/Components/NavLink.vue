<template>
    <Link
        :href="href"
        :class="[
            'group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors',
            active
                ? 'bg-primary-50 text-primary-700'
                : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
        ]"
    >
        <component
            :is="iconComponent"
            :class="[
                'mr-3 h-5 w-5 flex-shrink-0',
                active ? 'text-primary-700' : 'text-gray-400 group-hover:text-gray-500'
            ]"
        />
        <span class="flex-1">{{ $slots.default()[0].children }}</span>
        <span
            v-if="badge"
            class="ml-auto inline-flex items-center justify-center rounded-full bg-primary-600 px-2 py-0.5 text-xs font-bold text-white"
        >
            {{ badge }}
        </span>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import {
    HomeIcon,
    MapPinIcon,
    ArchiveBoxIcon,
    UsersIcon,
    DocumentTextIcon,
    ReceiptPercentIcon,
    ChatBubbleLeftRightIcon,
    Cog6ToothIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    href: String,
    active: Boolean,
    icon: String,
    badge: Number,
})

const iconMap = {
    home: HomeIcon,
    map: MapPinIcon,
    box: ArchiveBoxIcon,
    users: UsersIcon,
    'file-text': DocumentTextIcon,
    receipt: ReceiptPercentIcon,
    message: ChatBubbleLeftRightIcon,
    settings: Cog6ToothIcon,
}

const iconComponent = computed(() => iconMap[props.icon] || HomeIcon)
</script>
