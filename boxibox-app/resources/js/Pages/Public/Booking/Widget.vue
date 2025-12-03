<script setup>
import { ref, computed } from 'vue'
import {
    CubeIcon,
    MapPinIcon,
    CurrencyEuroIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    widget: Object,
    settings: Object,
    tenant: Object,
    sites: Array,
})

const selectedSite = ref(null)
const selectedBox = ref(null)

const availableBoxes = computed(() => {
    if (!selectedSite.value) return []
    const site = props.sites.find(s => s.id === selectedSite.value)
    return site?.boxes || []
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const continueBooking = () => {
    const box = availableBoxes.value.find(b => b.id === selectedBox.value)
    if (box) {
        // Redirect to full booking page with pre-selected box
        window.top.location.href = `${props.settings?.booking_url || '/book/tenant/' + props.tenant.id}?site=${selectedSite.value}&box=${selectedBox.value}`
    }
}
</script>

<template>
    <div
        class="min-h-screen font-sans"
        :style="{
            '--primary-color': settings?.primary_color || '#3B82F6',
            '--secondary-color': settings?.secondary_color || '#1E40AF',
        }"
    >
        <!-- Compact Widget -->
        <div v-if="widget.widget_type === 'compact'" class="p-4">
            <div class="bg-white rounded-xl shadow-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Réserver un box</h3>

                <select
                    v-model="selectedSite"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-3 focus:ring-2 focus:ring-blue-500"
                >
                    <option :value="null">Choisir un site</option>
                    <option v-for="site in sites" :key="site.id" :value="site.id">
                        {{ site.name }} ({{ site.available_boxes_count }} dispo.)
                    </option>
                </select>

                <select
                    v-if="selectedSite"
                    v-model="selectedBox"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-3 focus:ring-2 focus:ring-blue-500"
                >
                    <option :value="null">Choisir un box</option>
                    <option v-for="box in availableBoxes" :key="box.id" :value="box.id">
                        {{ box.name }} - {{ formatCurrency(box.current_price) }}/mois
                    </option>
                </select>

                <button
                    v-if="selectedBox"
                    @click="continueBooking"
                    class="w-full py-2 rounded-lg text-white font-medium"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    Continuer
                </button>
            </div>
        </div>

        <!-- Button Widget -->
        <div v-else-if="widget.widget_type === 'button'" class="p-4">
            <a
                :href="settings?.booking_url || '/book/tenant/' + tenant.id"
                target="_top"
                class="inline-flex items-center px-6 py-3 rounded-xl text-white font-medium"
                :style="{ backgroundColor: settings?.primary_color }"
            >
                <CubeIcon class="h-5 w-5 mr-2" />
                Réserver un box
            </a>
        </div>

        <!-- Full/Inline Widget -->
        <div v-else class="p-4">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <!-- Header -->
                <div
                    class="p-6 text-white"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    <h2 class="text-xl font-bold">{{ settings?.company_name || tenant.name }}</h2>
                    <p v-if="settings?.welcome_message" class="text-white/80 text-sm mt-1">
                        {{ settings.welcome_message }}
                    </p>
                </div>

                <div class="p-6">
                    <!-- Site Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Choisir un site</label>
                        <div class="grid grid-cols-1 gap-3">
                            <button
                                v-for="site in sites"
                                :key="site.id"
                                @click="selectedSite = site.id; selectedBox = null"
                                :class="[
                                    'p-4 rounded-xl border-2 text-left transition-all',
                                    selectedSite === site.id
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ site.name }}</p>
                                        <p class="text-sm text-gray-500">{{ site.city }}</p>
                                    </div>
                                    <span
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                        :style="{ backgroundColor: settings?.primary_color + '20', color: settings?.primary_color }"
                                    >
                                        {{ site.available_boxes_count }} dispo.
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Box Selection -->
                    <div v-if="selectedSite && availableBoxes.length > 0" class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Choisir un box</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                v-for="box in availableBoxes"
                                :key="box.id"
                                @click="selectedBox = box.id"
                                :class="[
                                    'p-4 rounded-xl border-2 text-left transition-all',
                                    selectedBox === box.id
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ box.name }}</p>
                                        <p class="text-sm text-gray-500">{{ box.volume }} m³</p>
                                    </div>
                                    <span
                                        class="font-bold"
                                        :style="{ color: settings?.primary_color }"
                                    >
                                        {{ formatCurrency(box.current_price) }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Continue Button -->
                    <button
                        v-if="selectedBox"
                        @click="continueBooking"
                        class="w-full py-3 rounded-xl text-white font-medium flex items-center justify-center"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        Continuer la réservation
                        <ArrowRightIcon class="h-5 w-5 ml-2" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
