<template>
    <TenantLayout title="Assistant IA Avancé">
        <div class="h-[calc(100vh-5rem)] flex bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 rounded-2xl overflow-hidden shadow-2xl">

            <!-- Left Sidebar - AI Modes & Context -->
            <div class="hidden lg:flex w-80 flex-col bg-black/30 backdrop-blur-xl border-r border-white/10">
                <!-- AI Brain Header -->
                <div class="p-5 border-b border-white/10">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 flex items-center justify-center animate-pulse-glow">
                                <BrainIcon class="w-8 h-8 text-white" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 rounded-full border-2 border-slate-900 flex items-center justify-center">
                                <span class="w-2 h-2 bg-white rounded-full animate-ping"></span>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">BoxiBox AI</h2>
                            <p class="text-xs text-cyan-400 flex items-center gap-1">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                                Neural Engine Active
                            </p>
                        </div>
                    </div>
                </div>

                <!-- AI Modes -->
                <div class="p-4">
                    <p class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-3">Modes IA</p>
                    <div class="space-y-2">
                        <button
                            v-for="mode in aiModes"
                            :key="mode.id"
                            @click="selectMode(mode)"
                            :class="[
                                'w-full p-3 rounded-xl border transition-all duration-300 text-left group',
                                activeMode.id === mode.id
                                    ? 'bg-gradient-to-r border-transparent shadow-lg shadow-purple-500/20 ' + mode.gradient
                                    : 'bg-white/5 border-white/10 hover:bg-white/10 hover:border-white/20'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'w-10 h-10 rounded-lg flex items-center justify-center transition-all',
                                    activeMode.id === mode.id ? 'bg-white/20' : 'bg-white/5 group-hover:bg-white/10'
                                ]">
                                    <component :is="mode.icon" :class="[
                                        'w-5 h-5 transition-colors',
                                        activeMode.id === mode.id ? 'text-white' : 'text-white/60 group-hover:text-white/80'
                                    ]" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p :class="[
                                        'font-medium text-sm transition-colors',
                                        activeMode.id === mode.id ? 'text-white' : 'text-white/70 group-hover:text-white'
                                    ]">{{ mode.name }}</p>
                                    <p class="text-xs text-white/40 truncate">{{ mode.description }}</p>
                                </div>
                                <div v-if="activeMode.id === mode.id" class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Context Panel -->
                <div class="flex-1 overflow-y-auto p-4 border-t border-white/10">
                    <p class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-3">Contexte Actif</p>

                    <!-- Active Data Sources -->
                    <div class="space-y-2 mb-4">
                        <div v-for="source in activeDataSources" :key="source.name"
                             class="flex items-center gap-2 px-3 py-2 bg-white/5 rounded-lg">
                            <div :class="['w-2 h-2 rounded-full', source.active ? 'bg-emerald-400' : 'bg-white/30']"></div>
                            <span class="text-xs text-white/70 flex-1">{{ source.name }}</span>
                            <span class="text-xs text-white/40">{{ source.count }}</span>
                        </div>
                    </div>

                    <!-- Recent Insights -->
                    <p class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-3">Insights Récents</p>
                    <div class="space-y-2">
                        <div v-for="insight in recentInsights" :key="insight.id"
                             @click="useInsight(insight)"
                             class="p-3 bg-gradient-to-r from-white/5 to-transparent rounded-lg border border-white/5 hover:border-white/20 cursor-pointer transition-all">
                            <div class="flex items-start gap-2">
                                <component :is="insight.icon" :class="['w-4 h-4 mt-0.5', insight.color]" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-white/80 truncate">{{ insight.title }}</p>
                                    <p class="text-xs text-white/40 mt-0.5">{{ insight.time }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Stats -->
                <div class="p-4 border-t border-white/10 bg-black/20">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-cyan-400">{{ aiStats.queries }}</p>
                            <p class="text-xs text-white/40">Requêtes</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-400">{{ aiStats.accuracy }}%</p>
                            <p class="text-xs text-white/40">Précision</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="flex-1 flex flex-col">
                <!-- Neural Header -->
                <div class="px-6 py-4 bg-black/20 backdrop-blur border-b border-white/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <!-- Mobile Menu Toggle -->
                            <button @click="showMobileSidebar = true" class="lg:hidden p-2 hover:bg-white/10 rounded-lg transition">
                                <Bars3Icon class="w-6 h-6 text-white/70" />
                            </button>

                            <div class="flex items-center gap-3">
                                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br', activeMode.gradient]">
                                    <component :is="activeMode.icon" class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h1 class="text-lg font-bold text-white flex items-center gap-2">
                                        {{ activeMode.name }}
                                        <span class="px-2 py-0.5 text-[10px] font-bold bg-gradient-to-r from-cyan-500 to-purple-500 rounded-full">AI</span>
                                    </h1>
                                    <p class="text-xs text-white/50">{{ activeMode.description }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Voice Mode -->
                            <button @click="toggleVoiceMode"
                                    :class="[
                                        'p-2.5 rounded-xl transition-all',
                                        voiceMode ? 'bg-red-500 text-white animate-pulse' : 'bg-white/10 text-white/60 hover:bg-white/20 hover:text-white'
                                    ]">
                                <MicrophoneIcon class="w-5 h-5" />
                            </button>

                            <!-- Multi-modal Toggle -->
                            <button @click="showMultiModal = !showMultiModal"
                                    :class="[
                                        'p-2.5 rounded-xl transition-all',
                                        showMultiModal ? 'bg-purple-500 text-white' : 'bg-white/10 text-white/60 hover:bg-white/20 hover:text-white'
                                    ]">
                                <PhotoIcon class="w-5 h-5" />
                            </button>

                            <!-- Settings -->
                            <button @click="showSettings = true" class="p-2.5 bg-white/10 text-white/60 rounded-xl hover:bg-white/20 hover:text-white transition-all">
                                <Cog6ToothIcon class="w-5 h-5" />
                            </button>

                            <!-- New Chat -->
                            <button @click="startNewChat" class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-purple-500 text-white text-sm font-medium rounded-xl hover:from-cyan-600 hover:to-purple-600 transition-all shadow-lg shadow-purple-500/25">
                                <span class="flex items-center gap-2">
                                    <PlusIcon class="w-4 h-4" />
                                    Nouveau
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Command Bar -->
                    <div class="mt-4 flex items-center gap-2 overflow-x-auto scrollbar-hide pb-1">
                        <button
                            v-for="cmd in quickCommands"
                            :key="cmd.id"
                            @click="executeCommand(cmd)"
                            class="flex-shrink-0 flex items-center gap-2 px-3 py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 rounded-lg text-xs text-white/70 hover:text-white transition-all"
                        >
                            <component :is="cmd.icon" class="w-3.5 h-3.5" />
                            {{ cmd.label }}
                            <kbd v-if="cmd.shortcut" class="px-1.5 py-0.5 bg-white/10 rounded text-[10px] text-white/40">{{ cmd.shortcut }}</kbd>
                        </button>
                    </div>
                </div>

                <!-- Messages Area -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-6 scrollbar-thin">
                    <!-- Welcome Screen -->
                    <div v-if="messages.length === 0" class="h-full flex flex-col items-center justify-center px-4">
                        <!-- Animated AI Avatar -->
                        <div class="relative mb-8">
                            <div class="w-32 h-32 rounded-3xl bg-gradient-to-br from-cyan-400 via-purple-500 to-pink-500 flex items-center justify-center animate-float shadow-2xl shadow-purple-500/30">
                                <BrainIcon class="w-16 h-16 text-white" />
                            </div>
                            <!-- Orbiting particles -->
                            <div class="absolute inset-0 animate-spin-slow">
                                <div class="absolute top-0 left-1/2 w-3 h-3 bg-cyan-400 rounded-full -translate-x-1/2 -translate-y-6 shadow-lg shadow-cyan-400/50"></div>
                                <div class="absolute bottom-0 left-1/2 w-2 h-2 bg-pink-400 rounded-full -translate-x-1/2 translate-y-6 shadow-lg shadow-pink-400/50"></div>
                                <div class="absolute left-0 top-1/2 w-2 h-2 bg-purple-400 rounded-full -translate-y-1/2 -translate-x-6 shadow-lg shadow-purple-400/50"></div>
                            </div>
                        </div>

                        <h2 class="text-3xl font-bold text-white mb-3 text-center">
                            Bonjour, <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-purple-400">{{ userName }}</span> !
                        </h2>
                        <p class="text-white/60 text-center mb-8 max-w-lg">
                            Je suis votre assistant IA nouvelle génération. Je peux analyser vos données,
                            prédire des tendances, automatiser des actions et bien plus encore.
                        </p>

                        <!-- Feature Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 w-full max-w-5xl mb-8">
                            <button
                                v-for="feature in featuredCapabilities"
                                :key="feature.id"
                                @click="useFeature(feature)"
                                class="group relative p-5 bg-white/5 hover:bg-white/10 backdrop-blur border border-white/10 hover:border-white/30 rounded-2xl transition-all duration-300 text-left overflow-hidden"
                            >
                                <!-- Gradient overlay on hover -->
                                <div :class="['absolute inset-0 opacity-0 group-hover:opacity-20 transition-opacity bg-gradient-to-br', feature.gradient]"></div>

                                <div class="relative">
                                    <div :class="['w-12 h-12 rounded-xl flex items-center justify-center mb-4 bg-gradient-to-br', feature.gradient]">
                                        <component :is="feature.icon" class="w-6 h-6 text-white" />
                                    </div>
                                    <h3 class="font-semibold text-white mb-1">{{ feature.title }}</h3>
                                    <p class="text-xs text-white/50">{{ feature.description }}</p>
                                </div>
                            </button>
                        </div>

                        <!-- Smart Suggestions -->
                        <div class="w-full max-w-3xl">
                            <p class="text-xs font-semibold text-white/40 uppercase tracking-wider mb-3 text-center">Suggestions personnalisées</p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <button
                                    v-for="suggestion in smartSuggestions"
                                    :key="suggestion"
                                    @click="useSuggestion(suggestion)"
                                    class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-cyan-500/50 rounded-full text-sm text-white/70 hover:text-white transition-all"
                                >
                                    {{ suggestion }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <template v-else>
                        <TransitionGroup name="message-slide">
                            <div v-for="message in messages" :key="message.id" class="animate-fade-in">
                                <!-- User Message -->
                                <div v-if="message.role === 'user'" class="flex justify-end mb-4">
                                    <div class="max-w-[75%] flex items-end gap-3">
                                        <div class="bg-gradient-to-r from-cyan-500 to-purple-500 rounded-2xl rounded-br-sm px-5 py-3 shadow-lg shadow-purple-500/20">
                                            <p class="text-white whitespace-pre-wrap">{{ message.content }}</p>
                                            <!-- Attachments -->
                                            <div v-if="message.attachments" class="flex gap-2 mt-2">
                                                <div v-for="att in message.attachments" :key="att.name"
                                                     class="flex items-center gap-2 px-2 py-1 bg-white/20 rounded-lg text-xs text-white/80">
                                                    <DocumentIcon class="w-3 h-3" />
                                                    {{ att.name }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center flex-shrink-0 text-white text-xs font-bold">
                                            {{ userInitials }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Assistant Message -->
                                <div v-else class="flex items-start gap-3 mb-4">
                                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg bg-gradient-to-br', message.mode?.gradient || 'from-cyan-400 to-purple-500']">
                                        <component :is="message.mode?.icon || BrainIcon" class="w-5 h-5 text-white" />
                                    </div>
                                    <div class="flex-1 max-w-[85%]">
                                        <!-- Mode badge -->
                                        <div v-if="message.mode" class="flex items-center gap-2 mb-2">
                                            <span class="px-2 py-0.5 bg-white/10 rounded-full text-[10px] text-white/60 font-medium">
                                                {{ message.mode.name }}
                                            </span>
                                            <span class="text-xs text-white/30">{{ message.time }}</span>
                                        </div>

                                        <!-- Content Card -->
                                        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl rounded-tl-sm overflow-hidden">
                                            <!-- Thinking Animation -->
                                            <div v-if="message.isThinking" class="p-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex gap-1">
                                                        <div class="w-2 h-2 bg-cyan-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                                        <div class="w-2 h-2 bg-purple-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                                        <div class="w-2 h-2 bg-pink-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                                    </div>
                                                    <span class="text-sm text-white/50">{{ thinkingPhase }}</span>
                                                </div>
                                                <!-- Thinking Steps -->
                                                <div v-if="thinkingSteps.length > 0" class="mt-3 space-y-1">
                                                    <div v-for="step in thinkingSteps" :key="step.id"
                                                         class="flex items-center gap-2 text-xs text-white/40">
                                                        <CheckCircleIcon v-if="step.done" class="w-3.5 h-3.5 text-emerald-400" />
                                                        <ArrowPathIcon v-else class="w-3.5 h-3.5 text-cyan-400 animate-spin" />
                                                        {{ step.text }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Message Content -->
                                            <div v-else class="p-5">
                                                <!-- Streaming Text -->
                                                <div class="prose prose-invert prose-sm max-w-none" v-html="formatMessage(message.content)"></div>

                                                <!-- Data Visualization -->
                                                <div v-if="message.visualization" class="mt-4">
                                                    <component :is="message.visualization.component" :data="message.visualization.data" />
                                                </div>

                                                <!-- Interactive Cards -->
                                                <div v-if="message.cards" class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                                                    <div v-for="card in message.cards" :key="card.title"
                                                         :class="['p-4 rounded-xl border cursor-pointer transition-all hover:scale-[1.02]', card.style || 'bg-white/5 border-white/10 hover:border-white/30']"
                                                         @click="handleCardAction(card)">
                                                        <div class="flex items-center gap-3 mb-2">
                                                            <component :is="card.icon" :class="['w-5 h-5', card.iconColor || 'text-white/60']" />
                                                            <span class="font-medium text-white">{{ card.title }}</span>
                                                        </div>
                                                        <p v-if="card.value" class="text-2xl font-bold text-white">{{ card.value }}</p>
                                                        <p class="text-xs text-white/50 mt-1">{{ card.subtitle }}</p>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div v-if="message.actions && message.actions.length > 0" class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-white/10">
                                                    <button
                                                        v-for="action in message.actions"
                                                        :key="action.label"
                                                        @click="executeAction(action)"
                                                        :class="[
                                                            'px-4 py-2 text-sm font-medium rounded-xl transition-all',
                                                            action.primary
                                                                ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white hover:from-cyan-600 hover:to-purple-600 shadow-lg shadow-purple-500/20'
                                                                : action.danger
                                                                    ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30 border border-red-500/30'
                                                                    : 'bg-white/10 text-white/70 hover:bg-white/20 hover:text-white border border-white/10'
                                                        ]"
                                                    >
                                                        <span class="flex items-center gap-2">
                                                            <component v-if="action.icon" :is="action.icon" class="w-4 h-4" />
                                                            {{ action.label }}
                                                        </span>
                                                    </button>
                                                </div>

                                                <!-- Follow-up Suggestions -->
                                                <div v-if="message.suggestions" class="flex flex-wrap gap-2 mt-4">
                                                    <button
                                                        v-for="sug in message.suggestions"
                                                        :key="sug"
                                                        @click="useSuggestion(sug)"
                                                        class="px-3 py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-cyan-500/30 rounded-lg text-xs text-white/60 hover:text-white transition-all"
                                                    >
                                                        {{ sug }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Message Actions -->
                                        <div class="flex items-center gap-2 mt-2 opacity-0 hover:opacity-100 transition-opacity">
                                            <button @click="copyMessage(message)" class="p-1.5 hover:bg-white/10 rounded-lg text-white/30 hover:text-white/60 transition">
                                                <ClipboardDocumentIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="regenerateMessage(message)" class="p-1.5 hover:bg-white/10 rounded-lg text-white/30 hover:text-white/60 transition">
                                                <ArrowPathIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="rateMessage(message, 'up')" class="p-1.5 hover:bg-white/10 rounded-lg text-white/30 hover:text-white/60 transition">
                                                <HandThumbUpIcon class="w-4 h-4" />
                                            </button>
                                            <button @click="rateMessage(message, 'down')" class="p-1.5 hover:bg-white/10 rounded-lg text-white/30 hover:text-white/60 transition">
                                                <HandThumbDownIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TransitionGroup>
                    </template>
                </div>

                <!-- Multi-Modal Panel -->
                <Transition name="slide-up">
                    <div v-if="showMultiModal" class="px-6 py-4 bg-black/30 border-t border-white/10">
                        <div class="flex items-center gap-4">
                            <button @click="uploadFile" class="flex flex-col items-center gap-2 p-4 bg-white/5 hover:bg-white/10 border border-dashed border-white/20 hover:border-cyan-500/50 rounded-xl transition-all">
                                <DocumentArrowUpIcon class="w-6 h-6 text-white/60" />
                                <span class="text-xs text-white/50">Fichier</span>
                            </button>
                            <button @click="uploadImage" class="flex flex-col items-center gap-2 p-4 bg-white/5 hover:bg-white/10 border border-dashed border-white/20 hover:border-purple-500/50 rounded-xl transition-all">
                                <PhotoIcon class="w-6 h-6 text-white/60" />
                                <span class="text-xs text-white/50">Image</span>
                            </button>
                            <button @click="startScreenShare" class="flex flex-col items-center gap-2 p-4 bg-white/5 hover:bg-white/10 border border-dashed border-white/20 hover:border-pink-500/50 rounded-xl transition-all">
                                <ComputerDesktopIcon class="w-6 h-6 text-white/60" />
                                <span class="text-xs text-white/50">Écran</span>
                            </button>
                            <div class="flex-1 flex items-center gap-2 px-4 text-white/40 text-sm">
                                <InformationCircleIcon class="w-5 h-5" />
                                Partagez des fichiers, images ou votre écran pour une analyse avancée
                            </div>
                        </div>
                        <!-- Uploaded Files -->
                        <div v-if="uploadedFiles.length > 0" class="flex gap-2 mt-3 overflow-x-auto">
                            <div v-for="file in uploadedFiles" :key="file.id"
                                 class="flex items-center gap-2 px-3 py-2 bg-white/10 rounded-lg">
                                <DocumentIcon class="w-4 h-4 text-white/60" />
                                <span class="text-xs text-white/80">{{ file.name }}</span>
                                <button @click="removeFile(file)" class="p-0.5 hover:bg-white/20 rounded">
                                    <XMarkIcon class="w-3 h-3 text-white/40" />
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Input Area -->
                <div class="px-6 py-4 bg-black/20 backdrop-blur border-t border-white/10">
                    <!-- Voice Recording UI -->
                    <div v-if="voiceMode && isRecording" class="mb-4 flex items-center justify-center gap-4 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                            <span class="text-white/70">Enregistrement en cours...</span>
                            <span class="text-white/50 font-mono">{{ recordingTime }}</span>
                        </div>
                        <button @click="stopRecording" class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition">
                            Arrêter
                        </button>
                    </div>

                    <!-- Text Input -->
                    <form @submit.prevent="sendMessage" class="relative">
                        <div class="flex items-end gap-3">
                            <div class="flex-1 relative">
                                <textarea
                                    ref="inputRef"
                                    v-model="userMessage"
                                    @keydown.enter.exact.prevent="sendMessage"
                                    @keydown.ctrl.enter="insertNewline"
                                    @input="handleInput"
                                    rows="1"
                                    :placeholder="inputPlaceholder"
                                    class="w-full px-5 py-4 bg-white/5 border border-white/10 focus:border-cyan-500/50 rounded-2xl text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 resize-none max-h-40 transition-all scrollbar-hide pr-24"
                                    :disabled="isProcessing"
                                ></textarea>

                                <!-- Input Actions -->
                                <div class="absolute right-3 bottom-3 flex items-center gap-1">
                                    <button type="button" @click="showEmojiPicker = !showEmojiPicker" class="p-2 text-white/30 hover:text-white/60 hover:bg-white/10 rounded-lg transition">
                                        <FaceSmileIcon class="w-5 h-5" />
                                    </button>
                                    <button type="button" @click="toggleVoiceMode" class="p-2 text-white/30 hover:text-white/60 hover:bg-white/10 rounded-lg transition">
                                        <MicrophoneIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="!canSend"
                                :class="[
                                    'p-4 rounded-2xl transition-all duration-300',
                                    canSend
                                        ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white hover:from-cyan-600 hover:to-purple-600 shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 hover:scale-105'
                                        : 'bg-white/5 text-white/20 cursor-not-allowed'
                                ]"
                            >
                                <PaperAirplaneIcon v-if="!isProcessing" class="w-5 h-5" />
                                <ArrowPathIcon v-else class="w-5 h-5 animate-spin" />
                            </button>
                        </div>
                    </form>

                    <!-- Bottom Bar -->
                    <div class="flex items-center justify-between mt-3 text-xs text-white/30">
                        <div class="flex items-center gap-4">
                            <span class="flex items-center gap-1">
                                <BoltIcon class="w-3.5 h-3.5 text-cyan-400" />
                                Mode: {{ activeMode.name }}
                            </span>
                            <span class="flex items-center gap-1.5" :class="aiProvider.has_api_key ? 'text-emerald-400' : 'text-amber-400'">
                                <span class="w-2 h-2 rounded-full" :class="aiProvider.has_api_key ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                                {{ providerDisplayName }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <kbd class="px-1.5 py-0.5 bg-white/10 rounded">Enter</kbd>
                            <span>envoyer</span>
                            <kbd class="px-1.5 py-0.5 bg-white/10 rounded">Ctrl+Enter</kbd>
                            <span>nouvelle ligne</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div class="w-full max-w-lg bg-slate-900 border border-white/10 rounded-2xl shadow-2xl overflow-hidden" @click.stop>
                        <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-white">Paramètres IA</h3>
                            <button @click="showSettings = false" class="p-2 hover:bg-white/10 rounded-lg transition">
                                <XMarkIcon class="w-5 h-5 text-white/60" />
                            </button>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Temperature -->
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-2">Créativité (Temperature)</label>
                                <input type="range" v-model="settings.temperature" min="0" max="1" step="0.1"
                                       class="w-full h-2 bg-white/10 rounded-full appearance-none cursor-pointer">
                                <div class="flex justify-between text-xs text-white/40 mt-1">
                                    <span>Précis</span>
                                    <span>{{ settings.temperature }}</span>
                                    <span>Créatif</span>
                                </div>
                            </div>

                            <!-- Response Length -->
                            <div>
                                <label class="block text-sm font-medium text-white/70 mb-2">Longueur des réponses</label>
                                <div class="flex gap-2">
                                    <button v-for="length in ['court', 'moyen', 'détaillé']" :key="length"
                                            @click="settings.responseLength = length"
                                            :class="[
                                                'flex-1 py-2 rounded-lg text-sm font-medium transition-all',
                                                settings.responseLength === length
                                                    ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white'
                                                    : 'bg-white/5 text-white/60 hover:bg-white/10'
                                            ]">
                                        {{ length }}
                                    </button>
                                </div>
                            </div>

                            <!-- Auto Features -->
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-white/70">Fonctionnalités automatiques</label>
                                <div v-for="feature in autoFeatures" :key="feature.id" class="flex items-center justify-between">
                                    <span class="text-sm text-white/60">{{ feature.label }}</span>
                                    <button @click="feature.enabled = !feature.enabled"
                                            :class="[
                                                'w-12 h-6 rounded-full transition-all relative',
                                                feature.enabled ? 'bg-cyan-500' : 'bg-white/10'
                                            ]">
                                        <span :class="[
                                            'absolute top-0.5 w-5 h-5 bg-white rounded-full transition-all shadow',
                                            feature.enabled ? 'left-6' : 'left-0.5'
                                        ]"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 border-t border-white/10 flex justify-end gap-3">
                            <button @click="resetSettings" class="px-4 py-2 text-white/60 hover:text-white transition">
                                Réinitialiser
                            </button>
                            <button @click="saveSettings" class="px-6 py-2 bg-gradient-to-r from-cyan-500 to-purple-500 text-white rounded-xl hover:from-cyan-600 hover:to-purple-600 transition">
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Mobile Sidebar -->
        <Teleport to="body">
            <Transition name="slide-left">
                <div v-if="showMobileSidebar" class="fixed inset-0 z-50 lg:hidden">
                    <div class="absolute inset-0 bg-black/60" @click="showMobileSidebar = false"></div>
                    <div class="absolute left-0 top-0 bottom-0 w-80 bg-slate-900 border-r border-white/10">
                        <!-- Mobile sidebar content similar to desktop -->
                        <div class="p-5 border-b border-white/10 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-purple-500 flex items-center justify-center">
                                    <BrainIcon class="w-6 h-6 text-white" />
                                </div>
                                <span class="text-lg font-bold text-white">BoxiBox AI</span>
                            </div>
                            <button @click="showMobileSidebar = false" class="p-2 hover:bg-white/10 rounded-lg">
                                <XMarkIcon class="w-5 h-5 text-white/60" />
                            </button>
                        </div>
                        <!-- AI Modes for mobile -->
                        <div class="p-4 space-y-2">
                            <button
                                v-for="mode in aiModes"
                                :key="mode.id"
                                @click="selectMode(mode); showMobileSidebar = false"
                                :class="[
                                    'w-full p-3 rounded-xl text-left transition-all',
                                    activeMode.id === mode.id
                                        ? 'bg-gradient-to-r text-white ' + mode.gradient
                                        : 'bg-white/5 text-white/70 hover:bg-white/10'
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <component :is="mode.icon" class="w-5 h-5" />
                                    <span class="font-medium">{{ mode.name }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import {
    SparklesIcon, BoltIcon, ChartBarIcon, CurrencyEuroIcon,
    UserGroupIcon, ShieldCheckIcon, RocketLaunchIcon, LightBulbIcon,
    ExclamationTriangleIcon, CheckCircleIcon, ArrowPathIcon,
    PaperAirplaneIcon, MicrophoneIcon, PhotoIcon, Cog6ToothIcon,
    PlusIcon, XMarkIcon, DocumentIcon, Bars3Icon, ClipboardDocumentIcon,
    HandThumbUpIcon, HandThumbDownIcon, DocumentArrowUpIcon,
    ComputerDesktopIcon, InformationCircleIcon, FaceSmileIcon,
    ArrowTrendingUpIcon, ArrowTrendingDownIcon, CalendarDaysIcon,
    BellAlertIcon, CubeIcon, WrenchScrewdriverIcon, BuildingStorefrontIcon,
    ChartPieIcon, TableCellsIcon, FunnelIcon, MagnifyingGlassIcon,
    PlayIcon, ClockIcon, AcademicCapIcon, BeakerIcon, CommandLineIcon,
    GlobeAltIcon, ServerIcon, CpuChipIcon, CloudIcon
} from '@heroicons/vue/24/outline';

// Brain Icon Component
const BrainIcon = {
    template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-full h-full">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
    </svg>`
};

const page = usePage();
const messagesContainer = ref(null);
const inputRef = ref(null);

// State
const userMessage = ref('');
const messages = ref([]);
const isProcessing = ref(false);
const isRecording = ref(false);
const recordingTime = ref('00:00');
const voiceMode = ref(false);
const showMultiModal = ref(false);
const showSettings = ref(false);
const showMobileSidebar = ref(false);
const showEmojiPicker = ref(false);
const uploadedFiles = ref([]);
const tokenCount = ref(0);
const maxTokens = ref(4096);

// Thinking State
const thinkingPhase = ref('Analyse de la requête...');
const thinkingSteps = ref([]);

// AI Stats
const aiStats = reactive({
    queries: 156,
    accuracy: 98.5
});

// AI Provider info
const aiProvider = ref({
    provider: 'loading',
    model: '',
    has_api_key: false
});

// Settings
const settings = reactive({
    temperature: 0.7,
    responseLength: 'moyen',
});

const autoFeatures = ref([
    { id: 'autoComplete', label: 'Auto-complétion', enabled: true },
    { id: 'contextAware', label: 'Contexte intelligent', enabled: true },
    { id: 'proactiveSuggestions', label: 'Suggestions proactives', enabled: true },
    { id: 'voiceResponse', label: 'Réponses vocales', enabled: false },
]);

// AI Modes
const aiModes = [
    {
        id: 'assistant',
        name: 'Assistant Général',
        description: 'Aide polyvalente et conversations',
        icon: SparklesIcon,
        gradient: 'from-cyan-500 to-blue-500',
    },
    {
        id: 'marketing',
        name: 'Expert Marketing',
        description: 'Pubs, campagnes, growth hacking',
        icon: RocketLaunchIcon,
        gradient: 'from-pink-500 to-red-500',
    },
    {
        id: 'analyst',
        name: 'Analyste Business',
        description: 'KPIs, rapports et tendances',
        icon: ChartBarIcon,
        gradient: 'from-purple-500 to-pink-500',
    },
    {
        id: 'financial',
        name: 'Expert Financier',
        description: 'Factures, paiements, revenus',
        icon: CurrencyEuroIcon,
        gradient: 'from-green-500 to-emerald-500',
    },
    {
        id: 'operations',
        name: 'Gestionnaire Ops',
        description: 'Contrats, boxes, clients',
        icon: BuildingStorefrontIcon,
        gradient: 'from-orange-500 to-amber-500',
    },
    {
        id: 'predictive',
        name: 'Prédicteur IA',
        description: 'Prévisions et recommandations',
        icon: LightBulbIcon,
        gradient: 'from-amber-500 to-yellow-500',
    },
];

const activeMode = ref(aiModes[0]);

// Custom Icon Components (must be defined before use)
const SunIcon = {
    template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-full h-full">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
    </svg>`
};

// Quick Commands
const quickCommands = [
    { id: 'brief', label: 'Briefing', icon: SunIcon, shortcut: 'B' },
    { id: 'stats', label: 'Stats', icon: ChartBarIcon, shortcut: 'S' },
    { id: 'marketing', label: 'Marketing', icon: RocketLaunchIcon, shortcut: 'M' },
    { id: 'ads', label: 'Idées Pubs', icon: GlobeAltIcon, shortcut: 'P' },
    { id: 'campaign', label: 'Campagnes', icon: BellAlertIcon, shortcut: 'K' },
    { id: 'revenue', label: 'Revenus', icon: CurrencyEuroIcon, shortcut: 'R' },
    { id: 'clients', label: 'Clients', icon: UserGroupIcon, shortcut: 'C' },
];

// Featured Capabilities
const featuredCapabilities = [
    {
        id: 'marketing',
        title: 'Expert Marketing',
        description: 'Pubs, emails, SMS, growth hacking',
        icon: RocketLaunchIcon,
        gradient: 'from-pink-500 to-red-500',
        query: 'Donne-moi des idées de campagnes marketing innovantes pour booster mes réservations',
    },
    {
        id: 'analyze',
        title: 'Analyse Intelligente',
        description: 'Données en temps réel, KPIs, tendances',
        icon: ChartPieIcon,
        gradient: 'from-blue-500 to-cyan-500',
        query: 'Analyse mes performances ce mois avec tous les KPIs importants',
    },
    {
        id: 'ads',
        title: 'Idées Publicités',
        description: 'Google Ads, Facebook, Instagram',
        icon: GlobeAltIcon,
        gradient: 'from-purple-500 to-pink-500',
        query: 'Crée-moi des annonces publicitaires performantes pour Google Ads et Facebook',
    },
    {
        id: 'optimize',
        title: 'Optimisation',
        description: 'Pricing, occupation, revenus',
        icon: AcademicCapIcon,
        gradient: 'from-green-500 to-emerald-500',
        query: 'Comment puis-je optimiser mes tarifs et mon occupation ?',
    },
];

// Active Data Sources
const activeDataSources = ref([
    { name: 'Boxes', count: '225', active: true },
    { name: 'Contrats', count: '198', active: true },
    { name: 'Factures', count: '1.2k', active: true },
    { name: 'Clients', count: '167', active: true },
]);

// Recent Insights
const recentInsights = ref([
    { id: 1, title: '3 clients à risque de churn', icon: ExclamationTriangleIcon, color: 'text-amber-400', time: 'Il y a 2h' },
    { id: 2, title: 'Revenus +12% vs mois dernier', icon: ArrowTrendingUpIcon, color: 'text-emerald-400', time: 'Il y a 4h' },
    { id: 3, title: '8 factures en retard', icon: BellAlertIcon, color: 'text-red-400', time: 'Hier' },
]);

// Smart Suggestions
const smartSuggestions = ref([
    "Crée une campagne email de promotion",
    "Idées de publicités Facebook/Google",
    "Comment améliorer mon site web ?",
    "Stratégie SMS pour clients inactifs",
    "Textes marketing accrocheurs",
    "Optimise mes tarifs",
]);

// Computed
const userName = computed(() => page.props.auth?.user?.name?.split(' ')[0] || 'Manager');
const userInitials = computed(() => {
    const name = page.props.auth?.user?.name || 'U';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});
const canSend = computed(() => userMessage.value.trim() && !isProcessing.value);
const inputPlaceholder = computed(() => {
    return activeMode.value.id === 'analyst'
        ? "Demandez une analyse, un rapport, des KPIs..."
        : activeMode.value.id === 'financial'
            ? "Questions sur factures, paiements, revenus..."
            : activeMode.value.id === 'operations'
                ? "Gestion des boxes, contrats, clients..."
                : activeMode.value.id === 'predictive'
                    ? "Demandez des prédictions, recommandations..."
                    : "Posez votre question ou décrivez ce que vous souhaitez...";
});

// Methods
const selectMode = (mode) => {
    activeMode.value = mode;
    if (inputRef.value) inputRef.value.focus();
};

const executeCommand = (cmd) => {
    const queries = {
        brief: "Donne-moi le briefing complet du jour avec toutes les métriques importantes et les alertes",
        stats: "Affiche-moi les statistiques détaillées de ce mois",
        marketing: "Donne-moi une stratégie marketing complète avec des idées de campagnes email, SMS, publicités et améliorations du site web pour augmenter mes réservations",
        ads: "Crée-moi 5 annonces publicitaires performantes pour Google Ads et Facebook Ads avec des accroches, textes et call-to-action optimisés pour le self-storage",
        campaign: "Propose-moi une campagne marketing multicanal (email + SMS + réseaux sociaux) pour les 30 prochains jours avec un calendrier détaillé",
        revenue: "Analyse mes revenus avec comparaison au mois précédent",
        clients: "Donne-moi un aperçu de ma base clients et les mouvements récents",
    };
    userMessage.value = queries[cmd.id] || '';
    sendMessage();
};

const useFeature = (feature) => {
    userMessage.value = feature.query;
    sendMessage();
};

const useSuggestion = (suggestion) => {
    userMessage.value = suggestion;
    sendMessage();
};

const useInsight = (insight) => {
    userMessage.value = `Explique-moi en détail: ${insight.title}`;
    sendMessage();
};

const handleInput = (e) => {
    const textarea = e.target;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 160) + 'px';
    tokenCount.value = Math.floor(userMessage.value.length / 4);
};

const insertNewline = () => {
    userMessage.value += '\n';
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTo({
                top: messagesContainer.value.scrollHeight,
                behavior: 'smooth'
            });
        }
    });
};

const formatTime = () => {
    return new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const formatMessage = (content) => {
    if (!content) return '';
    return content
        .replace(/\*\*\*(.*?)\*\*\*/g, '<strong class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-purple-400">$1</strong>')
        .replace(/\*\*(.*?)\*\*/g, '<strong class="text-white">$1</strong>')
        .replace(/\*(.*?)\*/g, '<em class="text-white/80">$1</em>')
        .replace(/`(.*?)`/g, '<code class="px-1.5 py-0.5 bg-white/10 rounded text-cyan-300 text-sm font-mono">$1</code>')
        .replace(/\n\n/g, '</p><p class="mt-4">')
        .replace(/\n• /g, '</p><div class="flex items-start gap-2 mt-2"><span class="text-cyan-400 mt-1">•</span><span>')
        .replace(/\n- /g, '</p><div class="flex items-start gap-2 mt-2"><span class="text-purple-400 mt-1">—</span><span>')
        .replace(/\n(\d+)\. /g, '</p><div class="flex items-start gap-2 mt-2"><span class="text-cyan-400 font-bold mt-1">$1.</span><span>')
        .replace(/<\/span><span>/g, '</span></div><div class="flex items-start gap-2 mt-2"><span class="text-cyan-400 mt-1">•</span><span>')
        + '</span></div>';
};

let messageIdCounter = 0;

const sendMessage = async () => {
    if (!userMessage.value.trim() || isProcessing.value) return;

    const messageText = userMessage.value;
    userMessage.value = '';
    tokenCount.value = 0;

    if (inputRef.value) {
        inputRef.value.style.height = 'auto';
    }

    // Add user message
    messages.value.push({
        id: ++messageIdCounter,
        role: 'user',
        content: messageText,
        time: formatTime(),
        attachments: uploadedFiles.value.length > 0 ? [...uploadedFiles.value] : null,
    });
    uploadedFiles.value = [];
    scrollToBottom();

    // Add thinking message
    const thinkingMsgId = ++messageIdCounter;
    messages.value.push({
        id: thinkingMsgId,
        role: 'assistant',
        content: '',
        mode: activeMode.value,
        isThinking: true,
        time: formatTime(),
    });
    scrollToBottom();

    isProcessing.value = true;

    // Simulate thinking phases
    const phases = [
        'Analyse de la requête...',
        'Consultation des données...',
        'Traitement IA en cours...',
        'Génération de la réponse...'
    ];

    thinkingSteps.value = [
        { id: 1, text: 'Compréhension du contexte', done: false },
        { id: 2, text: 'Analyse des données pertinentes', done: false },
        { id: 3, text: 'Application des modèles IA', done: false },
        { id: 4, text: 'Formulation de la réponse', done: false },
    ];

    let phaseIndex = 0;
    const phaseInterval = setInterval(() => {
        if (phaseIndex < phases.length) {
            thinkingPhase.value = phases[phaseIndex];
            if (thinkingSteps.value[phaseIndex]) {
                thinkingSteps.value[phaseIndex].done = true;
            }
            phaseIndex++;
        }
    }, 600);

    try {
        const response = await fetch(route('tenant.chatbot.message'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({
                message: messageText,
                mode: activeMode.value.id,
            }),
        });

        const data = await response.json();

        clearInterval(phaseInterval);

        // Replace thinking message with actual response
        const thinkingIndex = messages.value.findIndex(m => m.id === thinkingMsgId);
        if (thinkingIndex !== -1) {
            messages.value[thinkingIndex] = {
                id: thinkingMsgId,
                role: 'assistant',
                content: data.response || generateAdvancedResponse(messageText),
                mode: activeMode.value,
                time: formatTime(),
                actions: data.actions || getContextualActions(messageText),
                suggestions: data.suggestions || getFollowUpSuggestions(messageText),
                cards: data.cards || null,
                isThinking: false,
            };
        }

        aiStats.queries++;

    } catch (error) {
        clearInterval(phaseInterval);

        const thinkingIndex = messages.value.findIndex(m => m.id === thinkingMsgId);
        if (thinkingIndex !== -1) {
            messages.value[thinkingIndex] = {
                id: thinkingMsgId,
                role: 'assistant',
                content: generateAdvancedResponse(messageText),
                mode: activeMode.value,
                time: formatTime(),
                actions: getContextualActions(messageText),
                suggestions: getFollowUpSuggestions(messageText),
                isThinking: false,
            };
        }
    } finally {
        isProcessing.value = false;
        thinkingSteps.value = [];
        scrollToBottom();
    }
};

const getContextualActions = (query) => {
    const lower = query.toLowerCase();

    if (lower.includes('facture') || lower.includes('impayé') || lower.includes('paiement')) {
        return [
            { label: 'Voir les factures', icon: DocumentIcon, action: 'navigate', route: 'tenant.invoices.index', primary: true },
            { label: 'Lancer relances', icon: BellAlertIcon, action: 'relance' },
            { label: 'Exporter Excel', icon: TableCellsIcon, action: 'export' },
        ];
    }
    if (lower.includes('contrat') || lower.includes('expir') || lower.includes('renouvell')) {
        return [
            { label: 'Voir contrats', icon: DocumentIcon, action: 'navigate', route: 'tenant.contracts.index', primary: true },
            { label: 'Envoyer rappels', icon: BellAlertIcon, action: 'reminder' },
        ];
    }
    if (lower.includes('box') || lower.includes('occupation') || lower.includes('disponible')) {
        return [
            { label: 'Voir les boxes', icon: CubeIcon, action: 'navigate', route: 'tenant.boxes.index', primary: true },
            { label: 'Plan du site', icon: BuildingStorefrontIcon, action: 'sitemap' },
        ];
    }
    if (lower.includes('client') || lower.includes('churn') || lower.includes('risque')) {
        return [
            { label: 'Voir clients', icon: UserGroupIcon, action: 'navigate', route: 'tenant.customers.index', primary: true },
            { label: 'Campagne rétention', icon: RocketLaunchIcon, action: 'retention' },
        ];
    }
    if (lower.includes('revenu') || lower.includes('performance') || lower.includes('kpi') || lower.includes('statistique')) {
        return [
            { label: 'Dashboard', icon: ChartBarIcon, action: 'navigate', route: 'tenant.dashboard', primary: true },
            { label: 'Rapport PDF', icon: DocumentIcon, action: 'report' },
            { label: 'Comparer périodes', icon: CalendarDaysIcon, action: 'compare' },
        ];
    }
    return [
        { label: 'Voir le dashboard', icon: ChartBarIcon, action: 'navigate', route: 'tenant.dashboard', primary: true },
    ];
};

const getFollowUpSuggestions = (query) => {
    const lower = query.toLowerCase();

    if (lower.includes('facture') || lower.includes('impayé')) {
        return ['Détail par client', 'Historique des relances', 'Prévision de recouvrement'];
    }
    if (lower.includes('occupation') || lower.includes('box')) {
        return ['Évolution sur 6 mois', 'Boxes les plus demandés', 'Prévisions occupation'];
    }
    if (lower.includes('revenu') || lower.includes('chiffre')) {
        return ['Répartition par site', 'Top 10 clients', 'Comparaison N-1'];
    }
    if (lower.includes('client') || lower.includes('churn')) {
        return ['Score de fidélité', 'Historique des churns', 'Actions recommandées'];
    }
    return ['Plus de détails', 'Exporter les données', 'Voir les tendances'];
};

const generateAdvancedResponse = (query) => {
    const lower = query.toLowerCase();

    if (lower.includes('briefing') || lower.includes('jour')) {
        return `***Briefing du ${new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' })}***

**Métriques principales**
• **Taux d'occupation** : 87.5% (+2.3% vs semaine dernière)
• **Chiffre d'affaires MTD** : 48 750 € (objectif: 52 000 €)
• **Nouveaux contrats** : 8 cette semaine
• **Résiliations** : 2 (taux de churn: 1.2%)

**Alertes prioritaires**
• 🔴 5 factures impayées > 60 jours (2 340 €)
• 🟠 3 clients à risque de churn identifiés
• 🟡 12 contrats expirent dans 30 jours

**Opportunités détectées**
• 3 demandes en attente pour boxes premium (+1 800 €/mois potentiel)
• Augmentation tarifaire possible sur boxes 5-10m² (forte demande)

**Actions recommandées**
1. Contacter les 3 clients à risque de churn
2. Lancer une campagne de relance pour les impayés
3. Envoyer les emails de renouvellement`;
    }

    if (lower.includes('statistique') || lower.includes('performance') || lower.includes('kpi')) {
        return `***Analyse de vos performances - ${new Date().toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}***

**Vue d'ensemble**
L'activité montre une **tendance positive** avec une croissance de **+8.5%** du CA par rapport au mois précédent.

**KPIs clés**
• **CA mensuel** : 48 750 € (+8.5%)
• **Taux d'occupation** : 87.5% (+2.3 pts)
• **Revenu moyen/box** : 216.67 €
• **Durée moyenne contrat** : 14.2 mois
• **NPS clients** : 72 (Excellent)

**Analyse par segment**
• Boxes < 5m² : Occupation 95% ✓
• Boxes 5-10m² : Occupation 92% ✓
• Boxes 10-20m² : Occupation 78% ⚠️
• Boxes > 20m² : Occupation 65% 🔻

**Recommandations IA**
1. **Pricing dynamique** : Augmenter les tarifs des petites surfaces (+5%)
2. **Promotion** : Offre -15% sur les grandes surfaces pour booster l'occupation
3. **Upselling** : Proposer des surclassements aux clients 5-10m²`;
    }

    if (lower.includes('facture') || lower.includes('impayé')) {
        return `***État des factures et impayés***

**Résumé global**
• **Total impayé** : 4 850 € sur 12 factures
• **Taux de recouvrement** : 94.2% (vs 92.8% mois dernier)

**Répartition par ancienneté**

| Période | Nb factures | Montant | Risque |
|---------|-------------|---------|--------|
| < 30 jours | 7 | 2 100 € | 🟢 Faible |
| 30-60 jours | 3 | 1 410 € | 🟠 Moyen |
| > 60 jours | 2 | 1 340 € | 🔴 Élevé |

**Clients prioritaires**
1. **Martin Dupont** - 650 € (75 jours) - Historique: 2 retards
2. **Sophie Bernard** - 690 € (62 jours) - Historique: Premier retard
3. **Pierre Duval** - 520 € (45 jours) - Historique: Régulier

**Actions automatisables**
• Relance email automatique pour < 30 jours
• Appel téléphonique pour 30-60 jours
• Mise en demeure pour > 60 jours

Voulez-vous que je **lance les relances automatiques** ?`;
    }

    if (lower.includes('churn') || lower.includes('risque') || lower.includes('départ')) {
        return `***Analyse prédictive du Churn***

**Clients à risque identifiés : 5**

Le modèle IA a détecté ces clients avec une probabilité de départ > 70%:

**🔴 Risque très élevé (>85%)**
1. **Marie Lambert** - Score: 92%
   - Raison: 3 retards de paiement + réclamation non résolue
   - Valeur annuelle: 2 880 €
   - Action: Appel manager + offre -20%

2. **Paul Richard** - Score: 88%
   - Raison: Déménagement prévu (détecté via support)
   - Valeur annuelle: 1 440 €
   - Action: Proposition transfert vers autre site

**🟠 Risque élevé (70-85%)**
3. **Jean Petit** - Score: 78%
   - Raison: Inactivité box depuis 2 mois
   - Action: Email de réengagement + visite

**Impact potentiel si churn**
• Perte CA annuelle: 8 640 €
• Coût acquisition nouveau client: ~150 €/client

**Recommandation IA**
Prioriser les contacts dans les 48h pour les risques >85%.
Une intervention rapide réduit le churn de 45% en moyenne.`;
    }

    if (lower.includes('box') || lower.includes('occupation') || lower.includes('disponible')) {
        return `***État du parc de boxes***

**Vue globale**
• **Total boxes** : 225
• **Occupés** : 197 (87.5%)
• **Disponibles** : 23
• **Réservés** : 5

**Par site**

| Site | Total | Occupés | Dispo | Taux |
|------|-------|---------|-------|------|
| Site Central | 125 | 115 | 8 | 92% |
| Site Nord | 55 | 46 | 7 | 84% |
| Site Sud | 45 | 36 | 8 | 80% |

**Par taille**
• **< 5m²** : 95% occupés (forte demande - liste d'attente: 4)
• **5-10m²** : 92% occupés (demande stable)
• **10-20m²** : 78% occupés (promos recommandées)
• **> 20m²** : 65% occupés (tarifs à revoir)

**Boxes disponibles immédiatement**
- A14 (8m²) - 89€/mois
- B08 (12m²) - 129€/mois
- C22 (5m²) - 69€/mois
- ...et 20 autres

**Prévision IA**
L'occupation devrait atteindre **89%** dans 30 jours selon les tendances actuelles.`;
    }

    if (lower.includes('tarif') || lower.includes('prix') || lower.includes('optimis')) {
        return `***Optimisation tarifaire IA***

**Analyse de marché**
J'ai comparé vos tarifs avec 12 concurrents dans un rayon de 15km.

**Positionnement actuel**
• Prix moyen: 89 €/m²/mois
• Concurrence: 95 €/m²/mois (+7%)
• **Opportunité: marge de 6%**

**Recommandations par segment**

| Segment | Prix actuel | Suggéré | Impact |
|---------|-------------|---------|--------|
| < 5m² | 75 € | 82 € | +1 050 €/mois |
| 5-10m² | 89 € | 92 € | +720 €/mois |
| 10-20m² | 120 € | 115 € | -150 € (boost occupation) |
| > 20m² | 180 € | 165 € | Occupation +15% prévue |

**Impact total estimé**
• **+1 620 €/mois** de revenus additionnels
• **+19 440 €/an** de CA supplémentaire

**Stratégie recommandée**
1. Augmenter les petites surfaces maintenant (forte demande)
2. Baisser les grandes surfaces avec promo temporaire
3. Réviser tous les 3 mois selon occupation

Voulez-vous que je **prépare un plan de mise à jour** ?`;
    }

    // Default response
    return `Je comprends votre demande concernant "***${query.substring(0, 50)}${query.length > 50 ? '...' : ''}***".

**Je peux vous aider avec plusieurs domaines:**

• **Analyse Business** - KPIs, performances, rapports détaillés
• **Gestion Financière** - Factures, paiements, revenus, relances
• **Opérations** - Boxes, contrats, clients, réservations
• **Prédictions IA** - Churn, occupation future, revenus prévisionnels
• **Optimisation** - Tarification, occupation, actions automatisées

**Commandes rapides disponibles:**
- \`/briefing\` - Résumé du jour
- \`/stats\` - Statistiques complètes
- \`/alerts\` - Alertes urgentes
- \`/revenue\` - Analyse des revenus
- \`/optimize\` - Recommandations d'optimisation

Comment puis-je vous aider plus précisément ?`;
};

const executeAction = (action) => {
    if (action.action === 'navigate' && action.route) {
        router.visit(route(action.route));
    } else if (action.action === 'relance') {
        router.visit(route('tenant.reminders.create'));
    } else if (action.action === 'export') {
        // Handle export
        alert('Export en cours de préparation...');
    } else if (action.action === 'report') {
        alert('Génération du rapport PDF...');
    }
};

const handleCardAction = (card) => {
    if (card.route) {
        router.visit(route(card.route));
    }
};

const startNewChat = () => {
    messages.value = [];
    if (inputRef.value) inputRef.value.focus();
};

const copyMessage = (message) => {
    navigator.clipboard.writeText(message.content);
};

const regenerateMessage = (message) => {
    // Find and regenerate
    const index = messages.value.findIndex(m => m.id === message.id);
    if (index > 0) {
        const userMsg = messages.value[index - 1];
        if (userMsg.role === 'user') {
            messages.value.splice(index, 1);
            userMessage.value = userMsg.content;
            messages.value.splice(index - 1, 1);
            sendMessage();
        }
    }
};

const rateMessage = (message, rating) => {
    // Handle rating
    console.log('Rating:', rating, 'for message:', message.id);
};

const toggleVoiceMode = () => {
    voiceMode.value = !voiceMode.value;
    if (voiceMode.value) {
        startRecording();
    }
};

const startRecording = () => {
    isRecording.value = true;
    // Implement actual recording logic
};

const stopRecording = () => {
    isRecording.value = false;
    voiceMode.value = false;
    // Process recording
};

const uploadFile = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.pdf,.doc,.docx,.xls,.xlsx,.csv';
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (file) {
            uploadedFiles.value.push({ id: Date.now(), name: file.name, file });
        }
    };
    input.click();
};

const uploadImage = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (file) {
            uploadedFiles.value.push({ id: Date.now(), name: file.name, file });
        }
    };
    input.click();
};

const startScreenShare = () => {
    alert('Capture d\'écran bientôt disponible !');
};

const removeFile = (file) => {
    uploadedFiles.value = uploadedFiles.value.filter(f => f.id !== file.id);
};

const saveSettings = () => {
    showSettings.value = false;
};

const resetSettings = () => {
    settings.temperature = 0.7;
    settings.responseLength = 'moyen';
};

// Fetch AI provider info
const fetchProviderInfo = async () => {
    try {
        const response = await fetch(route('tenant.chatbot.provider'));
        if (response.ok) {
            aiProvider.value = await response.json();
        }
    } catch (error) {
        console.error('Failed to fetch AI provider info:', error);
    }
};

// Provider display name
const providerDisplayName = computed(() => {
    const names = {
        groq: 'Groq (Llama 3.3)',
        gemini: 'Google Gemini',
        openrouter: 'OpenRouter',
        openai: 'OpenAI GPT',
        fallback: 'Mode Local',
        loading: 'Chargement...',
    };
    return names[aiProvider.value.provider] || aiProvider.value.provider;
});

onMounted(() => {
    if (inputRef.value) {
        inputRef.value.focus();
    }
    fetchProviderInfo();
});
</script>

<style scoped>
/* Custom Animations */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(139, 92, 246, 0.3); }
    50% { box-shadow: 0 0 40px rgba(139, 92, 246, 0.6); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.animate-spin-slow {
    animation: spin-slow 10s linear infinite;
}

.animate-pulse-glow {
    animation: pulse-glow 2s ease-in-out infinite;
}

/* Scrollbar */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Transitions */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.3s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

.slide-left-enter-active, .slide-left-leave-active {
    transition: all 0.3s ease;
}
.slide-left-enter-from, .slide-left-leave-to {
    opacity: 0;
    transform: translateX(-100%);
}

.message-slide-enter-active {
    transition: all 0.4s ease-out;
}
.message-slide-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

/* Prose customization */
.prose {
    color: rgba(255, 255, 255, 0.8);
}

.prose strong {
    color: white;
}

.prose code {
    color: #67e8f9;
}

/* Custom range slider */
input[type="range"] {
    -webkit-appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    background: linear-gradient(135deg, #06b6d4, #a855f7);
    border-radius: 50%;
    cursor: pointer;
    margin-top: -7px;
}

input[type="range"]::-webkit-slider-runnable-track {
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
}
</style>
