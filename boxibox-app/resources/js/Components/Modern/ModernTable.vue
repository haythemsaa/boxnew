<template>
    <div class="w-full">
        <!-- En-tête avec contrôles -->
        <div v-if="showControls" class="mb-6 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <!-- Recherche -->
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-opacity-10 transition-all duration-200"
                />
            </div>

            <!-- Filtres -->
            <div class="flex gap-3">
                <slot name="filters"></slot>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-xl shadow-md">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-6 py-4 text-left text-sm font-semibold text-gray-700 cursor-pointer hover:text-primary-600 transition-colors"
                            @click="sort(column.key)"
                        >
                            <div class="flex items-center gap-2">
                                <span>{{ column.label }}</span>
                                <transition name="fade">
                                    <svg
                                        v-if="sortKey === column.key"
                                        class="w-4 h-4 text-primary-500"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            v-if="sortOrder === 'asc'"
                                            fill-rule="evenodd"
                                            d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                        <path
                                            v-else
                                            fill-rule="evenodd"
                                            d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </transition>
                            </div>
                        </th>
                        <th v-if="hasActions" class="px-6 py-4 text-right text-sm font-semibold text-gray-700">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <transition-group name="list" tag="tbody">
                        <tr
                            v-for="(row, index) in filteredSortedData"
                            :key="row.id || index"
                            class="hover:bg-primary-50 transition-colors duration-150 group"
                        >
                            <td
                                v-for="column in columns"
                                :key="column.key"
                                class="px-6 py-4 text-sm text-gray-700"
                            >
                                <slot :name="`cell-${column.key}`" :value="row[column.key]" :row="row">
                                    {{ row[column.key] }}
                                </slot>
                            </td>
                            <td v-if="hasActions" class="px-6 py-4 text-right">
                                <slot name="actions" :row="row"></slot>
                            </td>
                        </tr>
                    </transition-group>

                    <!-- Message vide -->
                    <tr v-if="filteredSortedData.length === 0">
                        <td :colspan="columns.length + (hasActions ? 1 : 0)" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                    ></path>
                                </svg>
                                <p class="text-gray-500 font-medium">{{ emptyMessage }}</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="showPagination && totalPages > 1" class="mt-6 flex justify-between items-center">
            <p class="text-sm text-gray-600">
                Affichage de <span class="font-semibold">{{ startIndex + 1 }}</span> à
                <span class="font-semibold">{{ endIndex }}</span> sur
                <span class="font-semibold">{{ filteredSortedData.length }}</span> résultats
            </p>
            <div class="flex gap-2">
                <button
                    @click="currentPage = Math.max(1, currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="px-4 py-2 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                    Précédent
                </button>
                <button
                    @click="currentPage = Math.min(totalPages, currentPage + 1)"
                    :disabled="currentPage === totalPages"
                    class="px-4 py-2 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                    Suivant
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    data: {
        type: Array,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
    hasActions: {
        type: Boolean,
        default: false,
    },
    showControls: {
        type: Boolean,
        default: true,
    },
    showPagination: {
        type: Boolean,
        default: true,
    },
    itemsPerPage: {
        type: Number,
        default: 10,
    },
    emptyMessage: {
        type: String,
        default: 'Aucune donnée trouvée',
    },
})

const searchQuery = ref('')
const currentPage = ref(1)
const sortKey = ref(null)
const sortOrder = ref('asc')

const filteredData = computed(() => {
    if (!searchQuery.value) return props.data

    const query = searchQuery.value.toLowerCase()
    return props.data.filter((row) =>
        Object.values(row).some((val) =>
            String(val).toLowerCase().includes(query)
        )
    )
})

const filteredSortedData = computed(() => {
    let data = [...filteredData.value]

    if (sortKey.value) {
        data.sort((a, b) => {
            const aVal = a[sortKey.value]
            const bVal = b[sortKey.value]

            if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
            if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
            return 0
        })
    }

    const start = (currentPage.value - 1) * props.itemsPerPage
    return data.slice(start, start + props.itemsPerPage)
})

const totalPages = computed(() => Math.ceil(filteredData.value.length / props.itemsPerPage))
const startIndex = computed(() => (currentPage.value - 1) * props.itemsPerPage)
const endIndex = computed(() => Math.min(currentPage.value * props.itemsPerPage, filteredData.value.length))

const sort = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortOrder.value = 'asc'
    }
}
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
