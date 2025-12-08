<script setup>
import { ref, computed, onMounted, nextTick, watch, reactive } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import axios from 'axios'
import {
    SparklesIcon,
    PaperAirplaneIcon,
    ArrowPathIcon,
    ChevronRightIcon,
    LightBulbIcon,
    ChartBarIcon,
    CurrencyEuroIcon,
    UserGroupIcon,
    BuildingStorefrontIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XMarkIcon,
    SunIcon,
    BoltIcon,
    ChatBubbleLeftRightIcon,
    ClockIcon,
    BellAlertIcon,
    Cog6ToothIcon,
    QuestionMarkCircleIcon,
    CommandLineIcon,
    CpuChipIcon,
    ShieldCheckIcon,
    BanknotesIcon,
    DocumentTextIcon,
    UserIcon,
    FolderIcon,
    MagnifyingGlassIcon,
    AdjustmentsHorizontalIcon,
    RocketLaunchIcon,
    EyeIcon,
    CalendarDaysIcon,
    ArchiveBoxIcon,
    PlayIcon,
    PauseIcon,
    StopIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    Bars3Icon,
    XCircleIcon,
    InformationCircleIcon,
    WrenchScrewdriverIcon,
    GlobeAltIcon,
    KeyIcon,
    SignalIcon,
    BeakerIcon,
    PresentationChartLineIcon,
    TableCellsIcon,
    CircleStackIcon,
    CloudIcon,
    FireIcon,
    HeartIcon,
    StarIcon,
    TrophyIcon,
    FlagIcon,
    MapPinIcon,
    PhoneIcon,
    EnvelopeIcon,
    LinkIcon,
    PhotoIcon,
    VideoCameraIcon,
    MicrophoneIcon,
    SpeakerWaveIcon,
} from '@heroicons/vue/24/outline'
import {
    SparklesIcon as SparklesSolid,
    BoltIcon as BoltSolid,
    FireIcon as FireSolid,
    StarIcon as StarSolid,
} from '@heroicons/vue/24/solid'

const props = defineProps({
    briefing: Object,
    conversationId: Number,
})

// State
const messages = ref([])
const inputMessage = ref('')
const isLoading = ref(false)
const conversationId = ref(props.conversationId || null)
const showBriefing = ref(true)
const messagesContainer = ref(null)
const inputRef = ref(null)
const isTyping = ref(false)
const showAgentPanel = ref(true)
const activeAgent = ref('orchestrator')
const showCommandPalette = ref(false)
const commandSearch = ref('')
const selectedTab = ref('chat') // chat, insights, agents, history
const agentStatus = reactive({
    orchestrator: { active: true, tasks: 0 },
    revenue: { active: false, tasks: 0 },
    collection: { active: false, tasks: 0 },
    churn: { active: false, tasks: 0 },
    forecast: { active: false, tasks: 0 },
})
const realtimeMetrics = reactive({
    occupancy: props.briefing?.summary?.occupancy || 0,
    revenue: props.briefing?.summary?.monthly_revenue || 0,
    overdue: props.briefing?.summary?.overdue_amount || 0,
    bookings: props.briefing?.summary?.pending_bookings || 0,
    trend: 'up',
    lastUpdate: new Date(),
})

// AI Agents Configuration
const aiAgents = [
    {
        id: 'orchestrator',
        name: 'Orchestrateur',
        description: 'Agent principal coordonnant tous les autres',
        icon: CpuChipIcon,
        color: 'from-purple-500 to-indigo-600',
        status: 'active',
        capabilities: ['Coordination', 'Analyse globale', 'Prioritisation'],
    },
    {
        id: 'revenue',
        name: 'Revenue Guardian',
        description: 'Optimisation des revenus et pricing',
        icon: BanknotesIcon,
        color: 'from-green-500 to-emerald-600',
        status: 'idle',
        capabilities: ['Dynamic pricing', 'Promotions', 'Yield management'],
    },
    {
        id: 'collection',
        name: 'Collection Agent',
        description: 'Gestion des impayés et relances',
        icon: ShieldCheckIcon,
        color: 'from-red-500 to-rose-600',
        status: 'idle',
        capabilities: ['Relances auto', 'Scoring risque', 'Plans paiement'],
    },
    {
        id: 'churn',
        name: 'Churn Predictor',
        description: 'Prédiction et prévention du churn',
        icon: UserGroupIcon,
        color: 'from-orange-500 to-amber-600',
        status: 'idle',
        capabilities: ['ML prediction', 'Alertes proactives', 'Rétention'],
    },
    {
        id: 'forecast',
        name: 'Forecast Engine',
        description: 'Prévisions et planification',
        icon: PresentationChartLineIcon,
        color: 'from-blue-500 to-cyan-600',
        status: 'idle',
        capabilities: ['Prévisions 30j', 'Saisonnalité', 'Scénarios'],
    },
]

// Quick commands for command palette
const commands = [
    { id: 'briefing', name: 'Briefing du jour', shortcut: 'B', category: 'Rapports', icon: SunIcon },
    { id: 'revenue', name: 'Analyse revenus', shortcut: 'R', category: 'Finance', icon: CurrencyEuroIcon },
    { id: 'occupancy', name: 'Taux occupation', shortcut: 'O', category: 'Opérations', icon: BuildingStorefrontIcon },
    { id: 'overdue', name: 'Impayés critiques', shortcut: 'I', category: 'Finance', icon: ExclamationTriangleIcon },
    { id: 'churn', name: 'Risque de churn', shortcut: 'C', category: 'Clients', icon: UserGroupIcon },
    { id: 'forecast', name: 'Prévisions 30j', shortcut: 'F', category: 'Prévisions', icon: ArrowTrendingUpIcon },
    { id: 'contracts', name: 'Contrats expirés', shortcut: 'X', category: 'Contrats', icon: DocumentTextIcon },
    { id: 'bookings', name: 'Réservations en attente', shortcut: 'K', category: 'Opérations', icon: CalendarDaysIcon },
    { id: 'optimize', name: 'Optimiser pricing', shortcut: 'P', category: 'Finance', icon: RocketLaunchIcon },
    { id: 'report', name: 'Générer rapport', shortcut: 'G', category: 'Rapports', icon: TableCellsIcon },
]

// Quick actions
const quickActions = [
    { id: 'briefing', label: 'Briefing du jour', icon: SunIcon, intent: 'daily_briefing', color: 'bg-yellow-500/20 text-yellow-400' },
    { id: 'revenue', label: 'Analyser revenus', icon: CurrencyEuroIcon, intent: 'revenue_analysis', color: 'bg-green-500/20 text-green-400' },
    { id: 'occupancy', label: 'Taux occupation', icon: BuildingStorefrontIcon, intent: 'occupancy_analysis', color: 'bg-blue-500/20 text-blue-400' },
    { id: 'overdue', label: 'Voir impayés', icon: ExclamationTriangleIcon, intent: 'overdue_analysis', color: 'bg-red-500/20 text-red-400' },
    { id: 'churn', label: 'Clients à risque', icon: UserGroupIcon, intent: 'customer_analysis', color: 'bg-orange-500/20 text-orange-400' },
    { id: 'forecast', label: 'Prévisions 30j', icon: ArrowTrendingUpIcon, intent: 'forecast', color: 'bg-purple-500/20 text-purple-400' },
]

// Insights data (simulated real-time)
const insights = ref([
    { id: 1, type: 'opportunity', title: 'Opportunité de revenus', message: '3 boxes premium disponibles pourraient générer +1,200€/mois', priority: 'high', agent: 'revenue' },
    { id: 2, type: 'warning', title: 'Risque de churn détecté', message: '2 clients montrent des signaux de départ imminent', priority: 'medium', agent: 'churn' },
    { id: 3, type: 'action', title: 'Relance automatique suggérée', message: '5 factures de plus de 30 jours nécessitent une action', priority: 'high', agent: 'collection' },
    { id: 4, type: 'forecast', title: 'Prévision positive', message: 'Occupation prévue à 92% dans 30 jours', priority: 'low', agent: 'forecast' },
])

// Conversation history
const conversationHistory = ref([
    { id: 1, title: 'Analyse des revenus Q4', date: '2024-01-15', messages: 12 },
    { id: 2, title: 'Optimisation pricing', date: '2024-01-14', messages: 8 },
    { id: 3, title: 'Rapport impayés', date: '2024-01-12', messages: 5 },
])

// Computed
const briefingAlerts = computed(() => props.briefing?.alerts || [])
const briefingOpportunities = computed(() => props.briefing?.opportunities || [])
const tipOfDay = computed(() => props.briefing?.tip_of_day || null)
const filteredCommands = computed(() => {
    if (!commandSearch.value) return commands
    const search = commandSearch.value.toLowerCase()
    return commands.filter(cmd =>
        cmd.name.toLowerCase().includes(search) ||
        cmd.category.toLowerCase().includes(search)
    )
})
const currentAgent = computed(() => aiAgents.find(a => a.id === activeAgent.value))

// Methods
const sendMessage = async (text = null) => {
    const messageText = text || inputMessage.value.trim()
    if (!messageText || isLoading.value) return

    // Add user message
    messages.value.push({
        id: Date.now(),
        role: 'user',
        content: messageText,
        timestamp: new Date(),
    })

    inputMessage.value = ''
    isLoading.value = true
    isTyping.value = true
    showBriefing.value = false
    selectedTab.value = 'chat'

    // Simulate agent activation
    activateRelevantAgent(messageText)

    await scrollToBottom()

    try {
        const response = await axios.post(route('tenant.copilot.chat'), {
            message: messageText,
            conversation_id: conversationId.value,
            agent: activeAgent.value,
        })

        conversationId.value = response.data.conversation_id

        // Simulate typing effect
        await simulateTyping(response.data)

    } catch (error) {
        console.error('Chat error:', error)
        messages.value.push({
            id: Date.now(),
            role: 'assistant',
            content: 'Désolé, une erreur est survenue. Veuillez réessayer.',
            timestamp: new Date(),
            isError: true,
        })
    } finally {
        isLoading.value = false
        isTyping.value = false
        deactivateAgents()
        await scrollToBottom()
    }
}

const activateRelevantAgent = (text) => {
    const lower = text.toLowerCase()
    if (lower.includes('revenu') || lower.includes('prix') || lower.includes('pricing')) {
        activeAgent.value = 'revenue'
        agentStatus.revenue.active = true
    } else if (lower.includes('impayé') || lower.includes('relance') || lower.includes('paiement')) {
        activeAgent.value = 'collection'
        agentStatus.collection.active = true
    } else if (lower.includes('churn') || lower.includes('risque') || lower.includes('client')) {
        activeAgent.value = 'churn'
        agentStatus.churn.active = true
    } else if (lower.includes('prévision') || lower.includes('forecast') || lower.includes('30 jours')) {
        activeAgent.value = 'forecast'
        agentStatus.forecast.active = true
    } else {
        activeAgent.value = 'orchestrator'
        agentStatus.orchestrator.active = true
    }
}

const deactivateAgents = () => {
    Object.keys(agentStatus).forEach(key => {
        agentStatus[key].active = false
    })
    agentStatus.orchestrator.active = true
}

const simulateTyping = async (data) => {
    const assistantMessage = {
        id: Date.now(),
        role: 'assistant',
        content: '',
        fullContent: data.message,
        actions: data.actions || [],
        suggestions: data.suggestions || [],
        data: data.data || null,
        charts: data.charts || null,
        timestamp: new Date(),
        isTyping: true,
        agent: activeAgent.value,
    }

    messages.value.push(assistantMessage)
    await scrollToBottom()

    const chars = data.message.split('')
    const chunkSize = 8

    for (let i = 0; i < chars.length; i += chunkSize) {
        assistantMessage.content += chars.slice(i, i + chunkSize).join('')
        await new Promise(resolve => setTimeout(resolve, 8))
        await scrollToBottom()
    }

    assistantMessage.isTyping = false
}

const scrollToBottom = async () => {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

const handleQuickAction = (action) => {
    const prompts = {
        briefing: 'Donne-moi le briefing complet du jour avec toutes les métriques importantes',
        revenue: 'Analyse détaillée de mes revenus avec tendances et recommandations',
        occupancy: 'Quel est mon taux d\'occupation actuel et comment l\'optimiser?',
        overdue: 'Montre-moi tous les impayés avec leur priorité et actions suggérées',
        churn: 'Quels clients risquent de partir et comment les retenir?',
        forecast: 'Quelles sont les prévisions détaillées pour les 30 prochains jours?',
    }
    sendMessage(prompts[action.id] || action.label)
}

const handleCommand = (command) => {
    showCommandPalette.value = false
    commandSearch.value = ''
    handleQuickAction({ id: command.id })
}

const handleSuggestion = (suggestion) => {
    sendMessage(suggestion)
}

const executeAction = async (action) => {
    if (action.type === 'link') {
        router.visit(route(action.route, action.params || {}))
    } else if (action.type === 'action') {
        isLoading.value = true
        try {
            const response = await axios.post(route('tenant.copilot.action'), {
                action: action.action,
                params: action.params || {},
            })

            if (response.data.redirect) {
                router.visit(response.data.redirect)
            } else {
                messages.value.push({
                    id: Date.now(),
                    role: 'assistant',
                    content: response.data.message,
                    timestamp: new Date(),
                })
            }
        } catch (error) {
            console.error('Action error:', error)
        } finally {
            isLoading.value = false
        }
    }
}

const formatMarkdown = (text) => {
    if (!text) return ''
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-semibold">$1</strong>')
    text = text.replace(/\n/g, '<br>')
    text = text.replace(/^• /gm, '<span class="text-primary-400 mr-2">•</span>')
    text = text.replace(/^- /gm, '<span class="text-primary-400 mr-2">—</span>')
    return text
}

const getAlertColor = (type) => {
    const colors = {
        danger: 'bg-red-500/10 border-red-500/30 text-red-400',
        warning: 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400',
        success: 'bg-green-500/10 border-green-500/30 text-green-400',
        info: 'bg-blue-500/10 border-blue-500/30 text-blue-400',
    }
    return colors[type] || colors.info
}

const getAlertIcon = (type) => {
    const icons = {
        danger: ExclamationTriangleIcon,
        warning: BellAlertIcon,
        success: CheckCircleIcon,
    }
    return icons[type] || LightBulbIcon
}

const getInsightIcon = (type) => {
    const icons = {
        opportunity: RocketLaunchIcon,
        warning: ExclamationTriangleIcon,
        action: BoltIcon,
        forecast: ArrowTrendingUpIcon,
    }
    return icons[type] || LightBulbIcon
}

const getInsightColor = (priority) => {
    const colors = {
        high: 'border-red-500/30 bg-red-500/5',
        medium: 'border-yellow-500/30 bg-yellow-500/5',
        low: 'border-green-500/30 bg-green-500/5',
    }
    return colors[priority] || colors.medium
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
    }).format(value || 0)
}

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const startNewConversation = () => {
    messages.value = []
    conversationId.value = null
    showBriefing.value = true
    activeAgent.value = 'orchestrator'
}

const toggleAgentPanel = () => {
    showAgentPanel.value = !showAgentPanel.value
}

const selectAgent = (agentId) => {
    activeAgent.value = agentId
    const agentPrompts = {
        orchestrator: 'Donne-moi une vue d\'ensemble de la situation actuelle',
        revenue: 'Analyse mes revenus et suggère des optimisations de pricing',
        collection: 'Montre-moi les impayés et lance les relances appropriées',
        churn: 'Identifie les clients à risque de churn et propose des actions de rétention',
        forecast: 'Génère des prévisions détaillées pour les 30 prochains jours',
    }
    sendMessage(agentPrompts[agentId])
}

const handleKeydown = (e) => {
    // Cmd/Ctrl + K to open command palette
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        showCommandPalette.value = !showCommandPalette.value
    }
    // Escape to close command palette
    if (e.key === 'Escape' && showCommandPalette.value) {
        showCommandPalette.value = false
    }
}

// Lifecycle
onMounted(() => {
    if (inputRef.value) {
        inputRef.value.focus()
    }
    window.addEventListener('keydown', handleKeydown)

    // Simulate real-time updates
    setInterval(() => {
        realtimeMetrics.lastUpdate = new Date()
    }, 30000)
})

watch(showBriefing, (val) => {
    if (!val && inputRef.value) {
        nextTick(() => inputRef.value.focus())
    }
})
</script>

<template>
    <Head title="BoxiBox Copilot - Centre de Commande IA" />

    <TenantLayout>
        <div class="h-[calc(100vh-80px)] flex">
            <!-- Left Panel - AI Agents -->
            <transition name="slide-left">
                <div v-if="showAgentPanel" class="w-72 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col">
                    <!-- Agent Panel Header -->
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-400 to-accent-400 flex items-center justify-center">
                                    <CpuChipIcon class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 text-sm">Agents IA</h3>
                                    <p class="text-xs text-gray-500">5 agents disponibles</p>
                                </div>
                            </div>
                            <button @click="toggleAgentPanel" class="p-1 text-gray-400 hover:text-gray-600">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Agents List -->
                    <div class="flex-1 overflow-y-auto p-3 space-y-2">
                        <button
                            v-for="agent in aiAgents"
                            :key="agent.id"
                            @click="selectAgent(agent.id)"
                            :class="[
                                'w-full p-3 rounded-xl border transition-all duration-200 text-left',
                                activeAgent === agent.id
                                    ? 'border-primary-400 bg-primary-50 shadow-sm'
                                    : 'border-gray-100 bg-white hover:border-gray-200 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex items-start gap-3">
                                <div :class="['w-10 h-10 rounded-lg bg-gradient-to-br flex items-center justify-center flex-shrink-0', agent.color]">
                                    <component :is="agent.icon" class="w-5 h-5 text-white" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-gray-900 text-sm truncate">{{ agent.name }}</h4>
                                        <span v-if="agentStatus[agent.id]?.active" class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ agent.description }}</p>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        <span
                                            v-for="cap in agent.capabilities.slice(0, 2)"
                                            :key="cap"
                                            class="px-1.5 py-0.5 text-[10px] bg-gray-100 text-gray-600 rounded"
                                        >
                                            {{ cap }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Agent Stats -->
                    <div class="p-3 border-t border-gray-100 bg-gray-50">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-white rounded-lg p-2 border border-gray-100">
                                <p class="text-xs text-gray-500">Requêtes aujourd'hui</p>
                                <p class="text-lg font-bold text-gray-900">47</p>
                            </div>
                            <div class="bg-white rounded-lg p-2 border border-gray-100">
                                <p class="text-xs text-gray-500">Actions exécutées</p>
                                <p class="text-lg font-bold text-gray-900">12</p>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col bg-gray-50">
                <!-- Header with Tabs -->
                <div class="flex-shrink-0 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between px-4 py-3">
                        <div class="flex items-center gap-3">
                            <button
                                v-if="!showAgentPanel"
                                @click="toggleAgentPanel"
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg"
                            >
                                <Bars3Icon class="w-5 h-5" />
                            </button>
                            <div class="flex items-center gap-2">
                                <div :class="['w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center', currentAgent?.color || 'from-primary-400 to-accent-400']">
                                    <component :is="currentAgent?.icon || SparklesIcon" class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h1 class="text-lg font-bold text-gray-900">{{ currentAgent?.name || 'BoxiBox Copilot' }}</h1>
                                    <p class="text-xs text-gray-500">{{ currentAgent?.description || 'Assistant IA Business' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Command Palette Trigger -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="showCommandPalette = true"
                                class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm text-gray-600 transition-colors"
                            >
                                <CommandLineIcon class="w-4 h-4" />
                                <span>Commandes</span>
                                <kbd class="px-1.5 py-0.5 bg-white rounded text-xs text-gray-400 border border-gray-200">⌘K</kbd>
                            </button>
                            <button
                                @click="startNewConversation"
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Nouvelle conversation"
                            >
                                <ArrowPathIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex items-center gap-1 px-4 pb-2">
                        <button
                            v-for="tab in [
                                { id: 'chat', label: 'Chat', icon: ChatBubbleLeftRightIcon },
                                { id: 'insights', label: 'Insights', icon: LightBulbIcon, badge: insights.length },
                                { id: 'history', label: 'Historique', icon: ClockIcon },
                            ]"
                            :key="tab.id"
                            @click="selectedTab = tab.id"
                            :class="[
                                'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                selectedTab === tab.id
                                    ? 'bg-primary-50 text-primary-600'
                                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
                            ]"
                        >
                            <component :is="tab.icon" class="w-4 h-4" />
                            {{ tab.label }}
                            <span
                                v-if="tab.badge"
                                class="px-1.5 py-0.5 text-xs bg-red-500 text-white rounded-full"
                            >
                                {{ tab.badge }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-hidden">
                    <!-- Chat Tab -->
                    <div v-show="selectedTab === 'chat'" class="h-full flex flex-col">
                        <!-- Messages Area -->
                        <div
                            ref="messagesContainer"
                            class="flex-1 overflow-y-auto p-4 space-y-4"
                        >
                            <!-- Welcome / Briefing Section -->
                            <div v-if="showBriefing && messages.length === 0" class="space-y-4">
                                <!-- Real-time Metrics Bar -->
                                <div class="bg-white rounded-xl border border-gray-200 p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                            <SignalIcon class="w-4 h-4 text-green-500" />
                                            Métriques en temps réel
                                        </h3>
                                        <span class="text-xs text-gray-400">
                                            Mis à jour: {{ formatTime(realtimeMetrics.lastUpdate) }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="relative">
                                            <div class="flex items-center gap-2 text-gray-500 mb-1">
                                                <BuildingStorefrontIcon class="w-4 h-4" />
                                                <span class="text-xs font-medium">Occupation</span>
                                            </div>
                                            <div class="text-2xl font-bold text-gray-900">{{ realtimeMetrics.occupancy }}%</div>
                                            <div class="absolute top-0 right-0">
                                                <ArrowTrendingUpIcon class="w-4 h-4 text-green-500" />
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <div class="flex items-center gap-2 text-gray-500 mb-1">
                                                <CurrencyEuroIcon class="w-4 h-4" />
                                                <span class="text-xs font-medium">Revenus</span>
                                            </div>
                                            <div class="text-2xl font-bold text-gray-900">{{ formatCurrency(realtimeMetrics.revenue) }}</div>
                                            <div class="absolute top-0 right-0">
                                                <ArrowTrendingUpIcon class="w-4 h-4 text-green-500" />
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <div class="flex items-center gap-2 text-gray-500 mb-1">
                                                <ExclamationTriangleIcon class="w-4 h-4" />
                                                <span class="text-xs font-medium">Impayés</span>
                                            </div>
                                            <div class="text-2xl font-bold text-red-600">{{ formatCurrency(realtimeMetrics.overdue) }}</div>
                                            <div class="absolute top-0 right-0">
                                                <ArrowTrendingDownIcon class="w-4 h-4 text-red-500" />
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <div class="flex items-center gap-2 text-gray-500 mb-1">
                                                <CalendarDaysIcon class="w-4 h-4" />
                                                <span class="text-xs font-medium">Réservations</span>
                                            </div>
                                            <div class="text-2xl font-bold text-gray-900">{{ realtimeMetrics.bookings }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Welcome Message -->
                                <div class="flex gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-primary-400 to-accent-400 rounded-xl flex items-center justify-center shadow-lg shadow-primary-200">
                                        <SparklesSolid class="w-5 h-5 text-white" />
                                    </div>
                                    <div class="flex-1 bg-white rounded-2xl rounded-tl-none p-4 border border-gray-200 shadow-sm">
                                        <p class="text-gray-900">
                                            {{ briefing?.greeting || 'Bonjour' }} !
                                            <span class="text-gray-600">Je suis votre assistant IA BoxiBox. Sélectionnez un agent spécialisé ou posez-moi une question.</span>
                                        </p>
                                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                                5 agents actifs
                                            </span>
                                            <span>•</span>
                                            <span>Appuyez sur ⌘K pour les commandes rapides</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Priority Alerts -->
                                <div v-if="briefingAlerts.length > 0" class="space-y-2">
                                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2 px-1">
                                        <BellAlertIcon class="w-4 h-4 text-red-500" />
                                        Alertes prioritaires
                                    </h3>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
                                        <div
                                            v-for="alert in briefingAlerts.slice(0, 4)"
                                            :key="alert.title"
                                            :class="['p-3 rounded-xl border bg-white', getAlertColor(alert.type)]"
                                        >
                                            <div class="flex items-start gap-3">
                                                <component :is="getAlertIcon(alert.type)" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                                                <div class="flex-1 min-w-0">
                                                    <h4 class="font-medium text-gray-900 text-sm">{{ alert.title }}</h4>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ alert.message }}</p>
                                                </div>
                                                <button
                                                    v-if="alert.action"
                                                    @click="router.visit(route(alert.action.route))"
                                                    class="flex-shrink-0 p-1 text-primary-500 hover:text-primary-600"
                                                >
                                                    <ChevronRightIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions Grid -->
                                <div class="space-y-2">
                                    <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2 px-1">
                                        <BoltSolid class="w-4 h-4 text-yellow-500" />
                                        Actions rapides
                                    </h3>
                                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                                        <button
                                            v-for="action in quickActions"
                                            :key="action.id"
                                            @click="handleQuickAction(action)"
                                            class="flex items-center gap-3 p-3 bg-white hover:bg-gray-50 border border-gray-200 hover:border-primary-300 rounded-xl transition-all group shadow-sm hover:shadow"
                                        >
                                            <div :class="['p-2 rounded-lg', action.color]">
                                                <component :is="action.icon" class="w-5 h-5" />
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ action.label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Tip of the Day -->
                                <div v-if="tipOfDay" class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-yellow-100 rounded-lg">
                                            <LightBulbIcon class="w-5 h-5 text-yellow-600" />
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-yellow-800 text-sm">Conseil du jour</h4>
                                            <p class="text-yellow-700 text-sm mt-1">{{ tipOfDay.tip }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat Messages -->
                            <template v-for="message in messages" :key="message.id">
                                <!-- User Message -->
                                <div v-if="message.role === 'user'" class="flex justify-end">
                                    <div class="max-w-[80%] bg-gradient-to-r from-primary-500 to-accent-500 text-white rounded-2xl rounded-br-none px-4 py-3 shadow-lg shadow-primary-200">
                                        {{ message.content }}
                                    </div>
                                </div>

                                <!-- Assistant Message -->
                                <div v-else class="flex gap-3">
                                    <div :class="['flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center shadow-lg',
                                        message.agent ? aiAgents.find(a => a.id === message.agent)?.color : 'bg-gradient-to-br from-primary-400 to-accent-400',
                                        message.agent ? '' : 'shadow-primary-200'
                                    ].join(' ').replace('bg-gradient-to-br', 'bg-gradient-to-br')">
                                        <component :is="message.agent ? aiAgents.find(a => a.id === message.agent)?.icon : SparklesIcon" class="w-5 h-5 text-white" />
                                    </div>
                                    <div class="flex-1 space-y-3">
                                        <div
                                            :class="[
                                                'bg-white rounded-2xl rounded-tl-none p-4 border shadow-sm',
                                                message.isError ? 'border-red-200' : 'border-gray-200'
                                            ]"
                                        >
                                            <div v-if="message.agent && message.agent !== 'orchestrator'" class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-100">
                                                <span class="text-xs font-medium text-gray-500">
                                                    {{ aiAgents.find(a => a.id === message.agent)?.name }}
                                                </span>
                                            </div>
                                            <div
                                                class="text-gray-700 prose prose-sm max-w-none"
                                                v-html="formatMarkdown(message.content)"
                                            ></div>

                                            <!-- Typing indicator -->
                                            <span v-if="message.isTyping" class="inline-flex items-center gap-1 mt-2">
                                                <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                                <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                                <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                            </span>
                                        </div>

                                        <!-- Actions -->
                                        <div v-if="message.actions && message.actions.length > 0" class="flex flex-wrap gap-2">
                                            <button
                                                v-for="action in message.actions"
                                                :key="action.label"
                                                @click="executeAction(action)"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white text-sm rounded-lg transition-colors shadow-sm"
                                            >
                                                {{ action.label }}
                                            </button>
                                        </div>

                                        <!-- Suggestions -->
                                        <div v-if="message.suggestions && message.suggestions.length > 0 && !message.isTyping" class="flex flex-wrap gap-2">
                                            <button
                                                v-for="suggestion in message.suggestions"
                                                :key="suggestion"
                                                @click="handleSuggestion(suggestion)"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg border border-gray-200 transition-colors"
                                            >
                                                <ChevronRightIcon class="w-3 h-3" />
                                                {{ suggestion }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Typing Indicator -->
                            <div v-if="isLoading && !messages.some(m => m.isTyping)" class="flex gap-3">
                                <div :class="['flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br', currentAgent?.color || 'from-primary-400 to-accent-400']">
                                    <component :is="currentAgent?.icon || SparklesIcon" class="w-5 h-5 text-white animate-pulse" />
                                </div>
                                <div class="bg-white rounded-2xl rounded-tl-none px-4 py-3 border border-gray-200 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">{{ currentAgent?.name }} analyse...</span>
                                        <div class="flex items-center gap-1">
                                            <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                            <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                            <span class="w-2 h-2 bg-primary-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Area -->
                        <div class="flex-shrink-0 p-4 bg-white border-t border-gray-200">
                            <form @submit.prevent="sendMessage()" class="flex items-center gap-3">
                                <div class="flex-1 relative">
                                    <input
                                        ref="inputRef"
                                        v-model="inputMessage"
                                        type="text"
                                        placeholder="Posez une question ou demandez une action..."
                                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition-all"
                                        :disabled="isLoading"
                                    />
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="showCommandPalette = true"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 transition-colors"
                                            title="Commandes (⌘K)"
                                        >
                                            <CommandLineIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="!inputMessage.trim() || isLoading"
                                    :class="[
                                        'p-3 rounded-xl transition-all shadow-lg',
                                        inputMessage.trim() && !isLoading
                                            ? 'bg-gradient-to-r from-primary-500 to-accent-500 hover:from-primary-600 hover:to-accent-600 text-white shadow-primary-200'
                                            : 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none'
                                    ]"
                                >
                                    <PaperAirplaneIcon class="w-5 h-5" />
                                </button>
                            </form>

                            <!-- Quick Suggestions -->
                            <div v-if="!showBriefing && messages.length > 0" class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="action in quickActions.slice(0, 4)"
                                    :key="action.id"
                                    @click="handleQuickAction(action)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs rounded-lg transition-colors"
                                >
                                    <component :is="action.icon" class="w-3.5 h-3.5" />
                                    {{ action.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Insights Tab -->
                    <div v-show="selectedTab === 'insights'" class="h-full overflow-y-auto p-4 space-y-3">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-gray-900">Insights IA</h2>
                            <span class="text-sm text-gray-500">{{ insights.length }} insights actifs</span>
                        </div>

                        <div
                            v-for="insight in insights"
                            :key="insight.id"
                            :class="['p-4 rounded-xl border bg-white', getInsightColor(insight.priority)]"
                        >
                            <div class="flex items-start gap-3">
                                <div :class="['p-2 rounded-lg',
                                    insight.priority === 'high' ? 'bg-red-100 text-red-600' :
                                    insight.priority === 'medium' ? 'bg-yellow-100 text-yellow-600' :
                                    'bg-green-100 text-green-600'
                                ]">
                                    <component :is="getInsightIcon(insight.type)" class="w-5 h-5" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-semibold text-gray-900">{{ insight.title }}</h4>
                                        <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full">
                                            {{ aiAgents.find(a => a.id === insight.agent)?.name }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">{{ insight.message }}</p>
                                    <div class="flex items-center gap-2 mt-3">
                                        <button class="px-3 py-1.5 bg-primary-500 hover:bg-primary-600 text-white text-sm rounded-lg transition-colors">
                                            Agir maintenant
                                        </button>
                                        <button class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition-colors">
                                            En savoir plus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div v-show="selectedTab === 'history'" class="h-full overflow-y-auto p-4 space-y-3">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-gray-900">Historique des conversations</h2>
                        </div>

                        <div
                            v-for="conv in conversationHistory"
                            :key="conv.id"
                            class="p-4 rounded-xl border border-gray-200 bg-white hover:border-primary-300 hover:shadow-sm transition-all cursor-pointer"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ conv.title }}</h4>
                                    <p class="text-sm text-gray-500 mt-0.5">{{ conv.date }} • {{ conv.messages }} messages</p>
                                </div>
                                <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Command Palette Modal -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showCommandPalette" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCommandPalette = false"></div>
                    <div class="relative min-h-screen flex items-start justify-center pt-[15vh] px-4">
                        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">
                            <!-- Search Input -->
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200">
                                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                                <input
                                    v-model="commandSearch"
                                    type="text"
                                    placeholder="Tapez une commande..."
                                    class="flex-1 bg-transparent border-none outline-none text-gray-900 placeholder-gray-400"
                                    autofocus
                                />
                                <kbd class="px-2 py-1 bg-gray-100 text-gray-500 text-xs rounded">ESC</kbd>
                            </div>

                            <!-- Commands List -->
                            <div class="max-h-80 overflow-y-auto p-2">
                                <div v-for="(group, category) in Object.groupBy(filteredCommands, c => c.category)" :key="category" class="mb-3">
                                    <h4 class="px-3 py-1 text-xs font-semibold text-gray-400 uppercase">{{ category }}</h4>
                                    <button
                                        v-for="cmd in group"
                                        :key="cmd.id"
                                        @click="handleCommand(cmd)"
                                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors"
                                    >
                                        <component :is="cmd.icon" class="w-5 h-5 text-gray-500" />
                                        <span class="flex-1 text-left text-gray-900">{{ cmd.name }}</span>
                                        <kbd class="px-1.5 py-0.5 bg-gray-100 text-gray-500 text-xs rounded">{{ cmd.shortcut }}</kbd>
                                    </button>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="px-4 py-2 border-t border-gray-200 bg-gray-50 flex items-center justify-between text-xs text-gray-500">
                                <span>↑↓ pour naviguer</span>
                                <span>↵ pour sélectionner</span>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </TenantLayout>
</template>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(143, 189, 86, 0.3);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(143, 189, 86, 0.5);
}

/* Animations */
@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}

.animate-bounce {
    animation: bounce 0.6s infinite;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
    transition: all 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-100%);
}

/* Line clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
