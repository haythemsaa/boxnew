<template>
    <div class="bg-white rounded-lg shadow-md p-6 animate-fade-in hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div :class="[`bg-${color}-100`, 'rounded-full p-3']">
                <component
                    :is="iconComponent"
                    :class="[`text-${color}-600`, 'h-8 w-8']"
                />
            </div>
            <div class="ml-4 flex-1">
                <p class="text-sm font-medium text-gray-600">{{ title }}</p>
                <p class="text-2xl font-bold text-gray-900">{{ value }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {
    MapPinIcon,
    ArchiveBoxIcon,
    CheckCircleIcon,
    LockClosedIcon,
    UsersIcon,
    DocumentTextIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    color: {
        type: String,
        default: 'blue',
    },
})

const iconMap = {
    map: MapPinIcon,
    box: ArchiveBoxIcon,
    check: CheckCircleIcon,
    lock: LockClosedIcon,
    users: UsersIcon,
    document: DocumentTextIcon,
    currency: CurrencyDollarIcon,
    chart: ChartBarIcon,
}

const iconComponent = computed(() => iconMap[props.icon] || ArchiveBoxIcon)
</script>
