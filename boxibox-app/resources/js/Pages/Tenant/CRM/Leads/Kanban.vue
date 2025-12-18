<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    columns: Object,
    metrics: Object,
    users: Array,
})

const draggedLead = ref(null)
const dragOverColumn = ref(null)
const selectedLeads = ref([])
const showBulkActions = ref(false)
const loading = ref(false)

const formatCurrency = (amount) => {
    if (!amount) return '-'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(amount)
}

const getPriorityStyle = (priority) => {
    const styles = {
        very_hot: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
        hot: { bg: 'bg-orange-100', text: 'text-orange-700', dot: 'bg-orange-500' },
        warm: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
        lukewarm: { bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
        cold: { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-500' },
    }
    return styles[priority] || styles.cold
}

const getColumnColor = (color) => {
    const colors = {
        blue: { bg: 'bg-blue-50', border: 'border-blue-200', header: 'bg-blue-100', text: 'text-blue-700' },
        cyan: { bg: 'bg-cyan-50', border: 'border-cyan-200', header: 'bg-cyan-100', text: 'text-cyan-700' },
        amber: { bg: 'bg-amber-50', border: 'border-amber-200', header: 'bg-amber-100', text: 'text-amber-700' },
        purple: { bg: 'bg-purple-50', border: 'border-purple-200', header: 'bg-purple-100', text: 'text-purple-700' },
        emerald: { bg: 'bg-emerald-50', border: 'border-emerald-200', header: 'bg-emerald-100', text: 'text-emerald-700' },
        gray: { bg: 'bg-gray-50', border: 'border-gray-200', header: 'bg-gray-100', text: 'text-gray-700' },
    }
    return colors[color] || colors.gray
}

const getScoreColor = (score) => {
    if (score >= 80) return 'text-emerald-600 bg-emerald-100'
    if (score >= 60) return 'text-amber-600 bg-amber-100'
    if (score >= 40) return 'text-orange-600 bg-orange-100'
    return 'text-gray-600 bg-gray-100'
}

// Drag and drop handlers
const onDragStart = (event, lead, columnKey) => {
    draggedLead.value = { lead, fromColumn: columnKey }
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', lead.id)
}

const onDragOver = (event, columnKey) => {
    event.preventDefault()
    event.dataTransfer.dropEffect = 'move'
    dragOverColumn.value = columnKey
}

const onDragLeave = () => {
    dragOverColumn.value = null
}

const onDrop = async (event, toColumn) => {
    event.preventDefault()
    dragOverColumn.value = null

    if (!draggedLead.value) return
    if (draggedLead.value.fromColumn === toColumn) {
        draggedLead.value = null
        return
    }

    const lead = draggedLead.value.lead
    loading.value = true

    try {
        await axios.post(route('tenant.crm.leads.update-status', lead.id), {
            status: toColumn,
        })

        // Refresh the page to get updated data
        router.reload({ only: ['columns'] })
    } catch (error) {
        console.error('Failed to update lead status:', error)
    } finally {
        loading.value = false
        draggedLead.value = null
    }
}

const onDragEnd = () => {
    draggedLead.value = null
    dragOverColumn.value = null
}

// Selection handlers
const toggleLeadSelection = (leadId) => {
    const index = selectedLeads.value.indexOf(leadId)
    if (index === -1) {
        selectedLeads.value.push(leadId)
    } else {
        selectedLeads.value.splice(index, 1)
    }
    showBulkActions.value = selectedLeads.value.length > 0
}

const clearSelection = () => {
    selectedLeads.value = []
    showBulkActions.value = false
}

const bulkAssign = async (userId) => {
    if (selectedLeads.value.length === 0) return

    loading.value = true
    try {
        await axios.post(route('tenant.crm.leads.bulk-update'), {
            lead_ids: selectedLeads.value,
            assigned_to: userId,
        })
        router.reload()
        clearSelection()
    } catch (error) {
        console.error('Bulk update failed:', error)
    } finally {
        loading.value = false
    }
}

const columnOrder = ['new', 'contacted', 'qualified', 'negotiation', 'converted', 'lost']

const orderedColumns = computed(() => {
    return columnOrder.map(key => ({
        key,
        ...props.columns[key],
    })).filter(col => col.name)
})

const totalPipelineValue = computed(() => {
    return columnOrder
        .filter(key => !['converted', 'lost'].includes(key))
        .reduce((sum, key) => sum + (props.columns[key]?.value || 0), 0)
})
</script>

<template>
    <TenantLayout title="Pipeline Leads">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50/30">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div class="animate-fade-in-up">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Pipeline Commercial</h1>
                                <p class="text-gray-500 text-sm">Glissez-déposez les leads pour changer leur statut</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 sm:mt-0 flex gap-3">
                        <Link
                            :href="route('tenant.crm.leads.index')"
                            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Vue Liste
                        </Link>
                        <Link
                            :href="route('tenant.crm.leads.create')"
                            class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-lg shadow-purple-500/25"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouveau Lead
                        </Link>
                    </div>
                </div>

                <!-- Metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <p class="text-xs font-medium text-gray-500">Total Leads</p>
                        <p class="text-2xl font-bold text-gray-900">{{ metrics?.total_leads || 0 }}</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <p class="text-xs font-medium text-gray-500">Nouveaux cette semaine</p>
                        <p class="text-2xl font-bold text-blue-600">+{{ metrics?.new_this_week || 0 }}</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <p class="text-xs font-medium text-gray-500">Taux de conversion</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ metrics?.conversion_rate || 0 }}%</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <p class="text-xs font-medium text-gray-500">Délai moyen conversion</p>
                        <p class="text-2xl font-bold text-purple-600">{{ metrics?.avg_time_to_convert || '-' }}j</p>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <p class="text-xs font-medium text-gray-500">Valeur Pipeline</p>
                        <p class="text-2xl font-bold text-amber-600">{{ formatCurrency(totalPipelineValue) }}</p>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div v-if="showBulkActions" class="bg-purple-600 text-white rounded-xl p-4 mb-6 flex items-center justify-between">
                        <span class="font-medium">{{ selectedLeads.length }} lead(s) sélectionné(s)</span>
                        <div class="flex items-center gap-3">
                            <select
                                @change="bulkAssign($event.target.value)"
                                class="px-3 py-1.5 rounded-lg text-sm bg-white/20 border border-white/30 text-white"
                            >
                                <option value="">Assigner à...</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <button @click="clearSelection" class="px-3 py-1.5 text-sm hover:bg-white/20 rounded-lg">
                                Annuler
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Kanban Board -->
                <div class="flex gap-4 overflow-x-auto pb-6" style="min-height: 600px;">
                    <div
                        v-for="column in orderedColumns"
                        :key="column.key"
                        class="flex-shrink-0 w-72"
                        @dragover="onDragOver($event, column.key)"
                        @dragleave="onDragLeave"
                        @drop="onDrop($event, column.key)"
                    >
                        <!-- Column Header -->
                        <div
                            :class="[
                                'rounded-t-xl px-4 py-3 border-b',
                                getColumnColor(column.color).header,
                                getColumnColor(column.color).border,
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold" :class="getColumnColor(column.color).text">{{ column.name }}</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-white/50" :class="getColumnColor(column.color).text">
                                        {{ column.count }}
                                    </span>
                                </div>
                                <span v-if="column.value > 0" class="text-xs font-medium text-gray-500">
                                    {{ formatCurrency(column.value) }}
                                </span>
                            </div>
                        </div>

                        <!-- Column Body -->
                        <div
                            :class="[
                                'rounded-b-xl p-2 min-h-[500px] transition-all duration-200',
                                getColumnColor(column.color).bg,
                                getColumnColor(column.color).border,
                                'border border-t-0',
                                dragOverColumn === column.key ? 'ring-2 ring-purple-400 ring-offset-2' : '',
                            ]"
                        >
                            <!-- Lead Cards -->
                            <div
                                v-for="lead in column.leads"
                                :key="lead.id"
                                draggable="true"
                                @dragstart="onDragStart($event, lead, column.key)"
                                @dragend="onDragEnd"
                                :class="[
                                    'bg-white rounded-xl p-3 mb-2 shadow-sm border border-gray-100 cursor-grab active:cursor-grabbing hover:shadow-md transition-all duration-150',
                                    selectedLeads.includes(lead.id) ? 'ring-2 ring-purple-400' : '',
                                ]"
                            >
                                <!-- Lead Header -->
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            :checked="selectedLeads.includes(lead.id)"
                                            @change="toggleLeadSelection(lead.id)"
                                            class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                                            @click.stop
                                        />
                                        <Link
                                            :href="route('tenant.crm.leads.show', lead.id)"
                                            class="font-semibold text-gray-900 text-sm hover:text-purple-600"
                                            @click.stop
                                        >
                                            {{ lead.name }}
                                        </Link>
                                    </div>
                                    <span
                                        v-if="lead.score"
                                        :class="[getScoreColor(lead.score), 'text-xs font-bold px-1.5 py-0.5 rounded']"
                                    >
                                        {{ lead.score }}
                                    </span>
                                </div>

                                <!-- Lead Info -->
                                <div class="space-y-1 text-xs text-gray-500">
                                    <p v-if="lead.company" class="truncate">{{ lead.company }}</p>
                                    <p v-if="lead.email" class="truncate">{{ lead.email }}</p>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span v-if="lead.site" class="inline-flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ lead.site }}
                                        </span>
                                        <span v-if="lead.budget_max" class="inline-flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ formatCurrency(lead.budget_max) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Lead Footer -->
                                <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100">
                                    <div class="flex items-center gap-1">
                                        <span
                                            v-if="lead.priority"
                                            :class="[getPriorityStyle(lead.priority).bg, getPriorityStyle(lead.priority).text]"
                                            class="text-xs px-1.5 py-0.5 rounded font-medium"
                                        >
                                            {{ lead.priority === 'very_hot' ? 'Très chaud' : lead.priority === 'hot' ? 'Chaud' : lead.priority === 'warm' ? 'Tiède' : lead.priority === 'lukewarm' ? 'Froid' : 'Très froid' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-400">
                                        <span v-if="lead.assigned_to" class="truncate max-w-20">{{ lead.assigned_to }}</span>
                                        <span v-if="lead.days_in_status > 0">{{ lead.days_in_status }}j</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="column.leads.length === 0" class="flex flex-col items-center justify-center py-8 text-gray-400">
                                <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-sm">Aucun lead</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Overlay -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="loading" class="fixed inset-0 bg-black/20 flex items-center justify-center z-50">
                        <div class="bg-white rounded-2xl p-6 shadow-2xl flex items-center gap-3">
                            <svg class="animate-spin h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="font-medium text-gray-700">Mise à jour...</span>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
.cursor-grab {
    cursor: grab;
}
.active\:cursor-grabbing:active {
    cursor: grabbing;
}
</style>
