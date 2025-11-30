<template>
    <div
        :class="[
            'relative overflow-hidden rounded-2xl p-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1',
            variant === 'gradient' ? gradientClass : 'bg-white border border-gray-100 shadow-sm'
        ]"
    >
        <!-- Background Pattern -->
        <div v-if="variant === 'gradient'" class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#grid)" />
            </svg>
        </div>

        <div class="relative flex items-start justify-between">
            <div class="flex-1">
                <p :class="[
                    'text-sm font-medium mb-1',
                    variant === 'gradient' ? 'text-white/80' : 'text-gray-500'
                ]">
                    {{ title }}
                </p>
                <p :class="[
                    'text-3xl font-bold tracking-tight',
                    variant === 'gradient' ? 'text-white' : 'text-gray-900'
                ]">
                    {{ animatedValue }}
                </p>
                <div v-if="subtitle || trend" class="mt-2 flex items-center space-x-2">
                    <span
                        v-if="trend"
                        :class="[
                            'inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full',
                            trend > 0
                                ? variant === 'gradient' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700'
                                : variant === 'gradient' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-700'
                        ]"
                    >
                        <component
                            :is="trend > 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                            class="w-3 h-3 mr-1"
                        />
                        {{ Math.abs(trend) }}%
                    </span>
                    <span :class="[
                        'text-xs',
                        variant === 'gradient' ? 'text-white/70' : 'text-gray-500'
                    ]">
                        {{ subtitle }}
                    </span>
                </div>
            </div>

            <!-- Icon -->
            <div
                :class="[
                    'flex h-12 w-12 items-center justify-center rounded-xl',
                    variant === 'gradient'
                        ? 'bg-white/20 text-white'
                        : iconBgClass
                ]"
            >
                <component :is="iconComponent" class="h-6 w-6" />
            </div>
        </div>

        <!-- Progress Bar -->
        <div v-if="progress !== undefined" class="mt-4">
            <div class="flex items-center justify-between text-xs mb-1">
                <span :class="variant === 'gradient' ? 'text-white/70' : 'text-gray-500'">
                    {{ progressLabel || 'Progression' }}
                </span>
                <span :class="variant === 'gradient' ? 'text-white' : 'text-gray-700'" class="font-medium">
                    {{ progress }}%
                </span>
            </div>
            <div :class="[
                'h-1.5 rounded-full overflow-hidden',
                variant === 'gradient' ? 'bg-white/20' : 'bg-gray-100'
            ]">
                <div
                    :class="[
                        'h-full rounded-full transition-all duration-1000 ease-out',
                        variant === 'gradient' ? 'bg-white' : progressBarClass
                    ]"
                    :style="{ width: `${progress}%` }"
                ></div>
            </div>
        </div>

        <!-- Sparkline mini chart -->
        <div v-if="sparkline && sparkline.length" class="mt-4 h-10">
            <svg class="w-full h-full" :viewBox="`0 0 ${sparkline.length * 10} 40`" preserveAspectRatio="none">
                <defs>
                    <linearGradient :id="`sparkline-gradient-${uid}`" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" :stop-color="variant === 'gradient' ? 'rgba(255,255,255,0.3)' : sparklineColor" />
                        <stop offset="100%" :stop-color="variant === 'gradient' ? 'rgba(255,255,255,0)' : 'transparent'" />
                    </linearGradient>
                </defs>
                <path
                    :d="sparklinePath"
                    fill="none"
                    :stroke="variant === 'gradient' ? 'rgba(255,255,255,0.8)' : sparklineColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    :d="sparklineAreaPath"
                    :fill="`url(#sparkline-gradient-${uid})`"
                />
            </svg>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import {
    MapPinIcon,
    ArchiveBoxIcon,
    CheckCircleIcon,
    LockClosedIcon,
    UsersIcon,
    DocumentTextIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    ClockIcon,
    BanknotesIcon,
    ShoppingCartIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CalendarIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    title: String,
    value: [String, Number],
    subtitle: String,
    icon: String,
    color: {
        type: String,
        default: 'blue',
    },
    variant: {
        type: String,
        default: 'default', // 'default' | 'gradient'
    },
    trend: Number,
    progress: Number,
    progressLabel: String,
    sparkline: Array,
    animate: {
        type: Boolean,
        default: true,
    },
})

const uid = Math.random().toString(36).substr(2, 9)
const animatedValue = ref(props.value)

const iconMap = {
    map: MapPinIcon,
    box: ArchiveBoxIcon,
    check: CheckCircleIcon,
    lock: LockClosedIcon,
    users: UsersIcon,
    'user-group': UserGroupIcon,
    document: DocumentTextIcon,
    currency: CurrencyDollarIcon,
    banknotes: BanknotesIcon,
    chart: ChartBarIcon,
    clock: ClockIcon,
    cart: ShoppingCartIcon,
    calendar: CalendarIcon,
}

const iconComponent = computed(() => iconMap[props.icon] || ArchiveBoxIcon)

const colorConfig = {
    blue: {
        gradient: 'bg-gradient-to-br from-blue-500 to-blue-700',
        iconBg: 'bg-blue-100 text-blue-600',
        progressBar: 'bg-blue-500',
        sparkline: '#3b82f6',
    },
    emerald: {
        gradient: 'bg-gradient-to-br from-emerald-500 to-emerald-700',
        iconBg: 'bg-emerald-100 text-emerald-600',
        progressBar: 'bg-emerald-500',
        sparkline: '#10b981',
    },
    green: {
        gradient: 'bg-gradient-to-br from-green-500 to-green-700',
        iconBg: 'bg-green-100 text-green-600',
        progressBar: 'bg-green-500',
        sparkline: '#22c55e',
    },
    purple: {
        gradient: 'bg-gradient-to-br from-purple-500 to-purple-700',
        iconBg: 'bg-purple-100 text-purple-600',
        progressBar: 'bg-purple-500',
        sparkline: '#a855f7',
    },
    pink: {
        gradient: 'bg-gradient-to-br from-pink-500 to-pink-700',
        iconBg: 'bg-pink-100 text-pink-600',
        progressBar: 'bg-pink-500',
        sparkline: '#ec4899',
    },
    indigo: {
        gradient: 'bg-gradient-to-br from-indigo-500 to-indigo-700',
        iconBg: 'bg-indigo-100 text-indigo-600',
        progressBar: 'bg-indigo-500',
        sparkline: '#6366f1',
    },
    teal: {
        gradient: 'bg-gradient-to-br from-teal-500 to-teal-700',
        iconBg: 'bg-teal-100 text-teal-600',
        progressBar: 'bg-teal-500',
        sparkline: '#14b8a6',
    },
    orange: {
        gradient: 'bg-gradient-to-br from-orange-500 to-orange-700',
        iconBg: 'bg-orange-100 text-orange-600',
        progressBar: 'bg-orange-500',
        sparkline: '#f97316',
    },
    yellow: {
        gradient: 'bg-gradient-to-br from-amber-500 to-amber-700',
        iconBg: 'bg-amber-100 text-amber-600',
        progressBar: 'bg-amber-500',
        sparkline: '#f59e0b',
    },
    red: {
        gradient: 'bg-gradient-to-br from-red-500 to-red-700',
        iconBg: 'bg-red-100 text-red-600',
        progressBar: 'bg-red-500',
        sparkline: '#ef4444',
    },
}

const gradientClass = computed(() => colorConfig[props.color]?.gradient || colorConfig.blue.gradient)
const iconBgClass = computed(() => colorConfig[props.color]?.iconBg || colorConfig.blue.iconBg)
const progressBarClass = computed(() => colorConfig[props.color]?.progressBar || colorConfig.blue.progressBar)
const sparklineColor = computed(() => colorConfig[props.color]?.sparkline || colorConfig.blue.sparkline)

// Sparkline path calculation
const sparklinePath = computed(() => {
    if (!props.sparkline || !props.sparkline.length) return ''

    const max = Math.max(...props.sparkline)
    const min = Math.min(...props.sparkline)
    const range = max - min || 1
    const points = props.sparkline.map((val, i) => {
        const x = i * 10 + 5
        const y = 35 - ((val - min) / range) * 30
        return `${x},${y}`
    })

    return `M ${points.join(' L ')}`
})

const sparklineAreaPath = computed(() => {
    if (!props.sparkline || !props.sparkline.length) return ''

    const max = Math.max(...props.sparkline)
    const min = Math.min(...props.sparkline)
    const range = max - min || 1
    const points = props.sparkline.map((val, i) => {
        const x = i * 10 + 5
        const y = 35 - ((val - min) / range) * 30
        return `${x},${y}`
    })

    const lastX = (props.sparkline.length - 1) * 10 + 5
    return `M 5,40 L ${points.join(' L ')} L ${lastX},40 Z`
})

// Animate number value
const animateNumber = () => {
    if (!props.animate || typeof props.value !== 'number') {
        animatedValue.value = props.value
        return
    }

    const start = 0
    const end = props.value
    const duration = 1000
    const startTime = performance.now()

    const animate = (currentTime) => {
        const elapsed = currentTime - startTime
        const progress = Math.min(elapsed / duration, 1)

        // Easing function
        const easeOutQuart = 1 - Math.pow(1 - progress, 4)

        animatedValue.value = Math.round(start + (end - start) * easeOutQuart)

        if (progress < 1) {
            requestAnimationFrame(animate)
        }
    }

    requestAnimationFrame(animate)
}

onMounted(() => {
    animateNumber()
})

watch(() => props.value, () => {
    animateNumber()
})
</script>
