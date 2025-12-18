<template>
    <TenantLayout>
        <div class="space-y-6">
            <!-- Header with entity info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold">
                            {{ getInitials(entity.name) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ entity.name }}</h1>
                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mt-1">
                                <span v-if="entity.email">
                                    <i class="fas fa-envelope mr-1"></i>{{ entity.email }}
                                </span>
                                <span v-if="entity.phone">
                                    <i class="fas fa-phone mr-1"></i>{{ entity.phone }}
                                </span>
                                <span v-if="entity.company">
                                    <i class="fas fa-building mr-1"></i>{{ entity.company }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusClass(entity.status)]">
                            {{ getStatusLabel(entity.status) }}
                        </span>
                        <Link
                            :href="route('tenant.crm.leads.show', entity.id)"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>Retour au lead
                        </Link>
                    </div>
                </div>

                <!-- Lead-specific info -->
                <div v-if="entity.type === 'lead'" class="grid grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Score</div>
                        <div class="text-2xl font-bold" :class="getScoreColor(entity.score)">{{ entity.score }}/100</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Priorite</div>
                        <span :class="['px-2 py-1 rounded text-sm font-medium', getPriorityClass(entity.priority)]">
                            {{ entity.priority }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Source</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ entity.source || 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Assigne a</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ entity.assigned_to || 'Non assigne' }}</div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                            <i class="fas fa-phone text-blue-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.calls }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Appels</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                            <i class="fas fa-envelope text-indigo-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.emails }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Emails</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                            <i class="fas fa-calendar text-purple-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.meetings }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">RDV</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                            <i class="fas fa-tasks text-amber-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending_tasks }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Taches en cours</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Timeline + Actions -->
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Timeline -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Filters -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <select
                                v-model="selectedType"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            >
                                <option value="">Tous les types</option>
                                <option v-for="(label, value) in filters.types" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <select
                                v-model="selectedUser"
                                class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            >
                                <option value="">Tous les utilisateurs</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            <button
                                @click="refreshTimeline"
                                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Timeline Items -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="p-6">
                            <div v-if="filteredTimeline.length === 0" class="text-center py-12">
                                <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucune interaction</h3>
                                <p class="text-gray-500 dark:text-gray-400">Commencez par ajouter une note ou enregistrer un appel.</p>
                            </div>

                            <div v-else class="relative">
                                <!-- Timeline line -->
                                <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                                <!-- Timeline items -->
                                <div class="space-y-6">
                                    <div
                                        v-for="item in filteredTimeline"
                                        :key="item.id"
                                        class="relative pl-12"
                                    >
                                        <!-- Icon -->
                                        <div :class="[
                                            'absolute left-0 w-10 h-10 rounded-full flex items-center justify-center',
                                            getIconBackground(item)
                                        ]">
                                            <i :class="['fas', 'fa-' + getItemIcon(item), getIconColor(item)]"></i>
                                        </div>

                                        <!-- Content -->
                                        <div :class="[
                                            'bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-100 dark:border-gray-600',
                                            item.type === 'interaction' && item.data.is_overdue ? 'border-red-300 dark:border-red-600' : ''
                                        ]">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <span class="font-medium text-gray-900 dark:text-white">
                                                        {{ getItemTitle(item) }}
                                                    </span>
                                                    <span v-if="item.type === 'interaction' && item.data.direction" class="text-sm text-gray-500 ml-2">
                                                        ({{ item.data.direction === 'inbound' ? 'Entrant' : 'Sortant' }})
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ item.data.created_at_human || formatDate(item.date) }}
                                                    </span>
                                                    <div v-if="item.type === 'interaction'" class="flex gap-1">
                                                        <button
                                                            v-if="!item.data.is_completed && ['task', 'meeting', 'call'].includes(item.data.type)"
                                                            @click="completeInteraction(item.data)"
                                                            class="p-1 text-green-600 hover:text-green-700"
                                                            title="Marquer comme termine"
                                                        >
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button
                                                            @click="editInteraction(item.data)"
                                                            class="p-1 text-gray-400 hover:text-gray-600"
                                                            title="Modifier"
                                                        >
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button
                                                            @click="deleteInteraction(item.data)"
                                                            class="p-1 text-red-400 hover:text-red-600"
                                                            title="Supprimer"
                                                        >
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Subject -->
                                            <div v-if="item.data.subject" class="font-semibold text-gray-800 dark:text-gray-200 mb-1">
                                                {{ item.data.subject }}
                                            </div>

                                            <!-- Content -->
                                            <div v-if="item.data.content || item.data.description" class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap">
                                                {{ item.data.content || item.data.description }}
                                            </div>

                                            <!-- Outcome -->
                                            <div v-if="item.data.outcome" class="mt-2 text-sm">
                                                <span class="text-gray-500 dark:text-gray-400">Resultat:</span>
                                                <span class="ml-1 text-gray-700 dark:text-gray-300">{{ item.data.outcome }}</span>
                                            </div>

                                            <!-- Duration for calls -->
                                            <div v-if="item.data.formatted_duration" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                <i class="fas fa-clock mr-1"></i>{{ item.data.formatted_duration }}
                                            </div>

                                            <!-- Scheduled date -->
                                            <div v-if="item.data.scheduled_at && !item.data.is_completed" class="mt-2 flex items-center gap-2">
                                                <span :class="[
                                                    'text-xs px-2 py-1 rounded',
                                                    item.data.is_overdue ? 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                                ]">
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    {{ item.data.is_overdue ? 'En retard' : 'Prevu le' }} {{ item.data.scheduled_at }}
                                                </span>
                                            </div>

                                            <!-- Completed badge -->
                                            <div v-if="item.data.is_completed" class="mt-2">
                                                <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                                    <i class="fas fa-check mr-1"></i>Termine le {{ item.data.completed_at }}
                                                </span>
                                            </div>

                                            <!-- Attachments -->
                                            <div v-if="item.data.attachments?.length > 0" class="mt-3 flex flex-wrap gap-2">
                                                <a
                                                    v-for="att in item.data.attachments"
                                                    :key="att.id"
                                                    :href="att.url"
                                                    target="_blank"
                                                    class="flex items-center gap-2 px-2 py-1 bg-gray-100 dark:bg-gray-600 rounded text-sm hover:bg-gray-200 dark:hover:bg-gray-500"
                                                >
                                                    <i :class="['fas', 'fa-' + att.icon]"></i>
                                                    <span class="truncate max-w-[150px]">{{ att.name }}</span>
                                                    <span class="text-xs text-gray-500">{{ att.size }}</span>
                                                </a>
                                            </div>

                                            <!-- User -->
                                            <div v-if="item.data.user" class="mt-3 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <div class="w-5 h-5 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-[10px] font-medium">
                                                    {{ item.data.user.name?.charAt(0) }}
                                                </div>
                                                {{ item.data.user.name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Sidebar -->
                <div class="space-y-4">
                    <!-- Quick Note -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-sticky-note mr-2 text-yellow-500"></i>Note rapide
                        </h3>
                        <textarea
                            v-model="quickNote"
                            rows="3"
                            placeholder="Ajouter une note..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                        ></textarea>
                        <button
                            @click="addQuickNote"
                            :disabled="!quickNote.trim() || submitting"
                            class="mt-2 w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-50"
                        >
                            <i class="fas fa-plus mr-2"></i>Ajouter la note
                        </button>
                    </div>

                    <!-- Log Call -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-phone mr-2 text-blue-500"></i>Enregistrer un appel
                        </h3>
                        <div class="space-y-3">
                            <div class="flex gap-2">
                                <button
                                    @click="callForm.direction = 'outbound'"
                                    :class="[
                                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium',
                                        callForm.direction === 'outbound' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                                    ]"
                                >
                                    <i class="fas fa-phone-alt mr-1"></i>Sortant
                                </button>
                                <button
                                    @click="callForm.direction = 'inbound'"
                                    :class="[
                                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium',
                                        callForm.direction === 'inbound' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                                    ]"
                                >
                                    <i class="fas fa-phone-volume mr-1"></i>Entrant
                                </button>
                            </div>
                            <select
                                v-model="callForm.outcome"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            >
                                <option value="">Resultat de l'appel...</option>
                                <option value="answered">Repondu</option>
                                <option value="voicemail">Messagerie vocale</option>
                                <option value="no_answer">Pas de reponse</option>
                                <option value="busy">Occupe</option>
                                <option value="callback_scheduled">Rappel programme</option>
                            </select>
                            <div class="flex gap-2">
                                <input
                                    v-model.number="callForm.duration_minutes"
                                    type="number"
                                    min="0"
                                    placeholder="Duree (min)"
                                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                />
                            </div>
                            <textarea
                                v-model="callForm.content"
                                rows="2"
                                placeholder="Notes sur l'appel..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            ></textarea>
                            <button
                                @click="logCall"
                                :disabled="!callForm.outcome || submitting"
                                class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50"
                            >
                                <i class="fas fa-save mr-2"></i>Enregistrer l'appel
                            </button>
                        </div>
                    </div>

                    <!-- Schedule Task/Meeting -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
                            <i class="fas fa-calendar-plus mr-2 text-purple-500"></i>Planifier
                        </h3>
                        <div class="space-y-3">
                            <select
                                v-model="scheduleForm.type"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            >
                                <option value="task">Tache</option>
                                <option value="meeting">Rendez-vous</option>
                                <option value="call">Appel</option>
                                <option value="visit">Visite</option>
                            </select>
                            <input
                                v-model="scheduleForm.subject"
                                type="text"
                                placeholder="Sujet..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            />
                            <input
                                v-model="scheduleForm.scheduled_at"
                                type="datetime-local"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            />
                            <select
                                v-model="scheduleForm.priority"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                            >
                                <option value="low">Priorite basse</option>
                                <option value="normal">Priorite normale</option>
                                <option value="high">Priorite haute</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <button
                                @click="scheduleTask"
                                :disabled="!scheduleForm.subject || !scheduleForm.scheduled_at || submitting"
                                class="w-full px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50"
                            >
                                <i class="fas fa-calendar-check mr-2"></i>Planifier
                            </button>
                        </div>
                    </div>

                    <!-- Add Interaction (Full Form) -->
                    <button
                        @click="showFullForm = true"
                        class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 font-medium"
                    >
                        <i class="fas fa-plus mr-2"></i>Ajouter une interaction
                    </button>
                </div>
            </div>
        </div>

        <!-- Full Interaction Modal -->
        <Teleport to="body">
            <div v-if="showFullForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Nouvelle interaction</h2>
                            <button @click="showFullForm = false" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                <select
                                    v-model="interactionForm.type"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option v-for="(label, value) in filters.types" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="['call', 'email', 'sms'].includes(interactionForm.type)">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direction</label>
                                <select
                                    v-model="interactionForm.direction"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="outbound">Sortant</option>
                                    <option value="inbound">Entrant</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sujet</label>
                            <input
                                v-model="interactionForm.subject"
                                type="text"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contenu</label>
                            <textarea
                                v-model="interactionForm.content"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resultat</label>
                                <input
                                    v-model="interactionForm.outcome"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sentiment</label>
                                <select
                                    v-model="interactionForm.sentiment"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">Non defini</option>
                                    <option value="positive">Positif</option>
                                    <option value="neutral">Neutre</option>
                                    <option value="negative">Negatif</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date programmee</label>
                                <input
                                    v-model="interactionForm.scheduled_at"
                                    type="datetime-local"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priorite</label>
                                <select
                                    v-model="interactionForm.priority"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="low">Basse</option>
                                    <option value="normal">Normale</option>
                                    <option value="high">Haute</option>
                                    <option value="urgent">Urgente</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button
                            @click="showFullForm = false"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800"
                        >
                            Annuler
                        </button>
                        <button
                            @click="submitInteraction"
                            :disabled="submitting"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            <i class="fas fa-save mr-2"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    entity: Object,
    timeline: Array,
    stats: Object,
    filters: Object,
    users: Array,
});

const selectedType = ref('');
const selectedUser = ref('');
const quickNote = ref('');
const submitting = ref(false);
const showFullForm = ref(false);

const callForm = ref({
    direction: 'outbound',
    outcome: '',
    duration_minutes: null,
    content: '',
});

const scheduleForm = ref({
    type: 'task',
    subject: '',
    scheduled_at: '',
    priority: 'normal',
});

const interactionForm = ref({
    type: 'note',
    direction: 'outbound',
    subject: '',
    content: '',
    outcome: '',
    sentiment: '',
    scheduled_at: '',
    priority: 'normal',
});

const filteredTimeline = computed(() => {
    return props.timeline.filter(item => {
        if (selectedType.value && item.type === 'interaction' && item.data.type !== selectedType.value) {
            return false;
        }
        if (selectedUser.value && item.data.user?.id !== parseInt(selectedUser.value)) {
            return false;
        }
        return true;
    });
});

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || '??';
};

const getStatusClass = (status) => {
    const classes = {
        'new': 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        'contacted': 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-300',
        'qualified': 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
        'negotiation': 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
        'converted': 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        'lost': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[status] || classes['new'];
};

const getStatusLabel = (status) => {
    const labels = {
        'new': 'Nouveau',
        'contacted': 'Contacte',
        'qualified': 'Qualifie',
        'negotiation': 'Negociation',
        'converted': 'Converti',
        'lost': 'Perdu',
    };
    return labels[status] || status;
};

const getScoreColor = (score) => {
    if (score >= 70) return 'text-green-600';
    if (score >= 40) return 'text-amber-600';
    return 'text-red-600';
};

const getPriorityClass = (priority) => {
    const classes = {
        'very_hot': 'bg-red-100 text-red-700',
        'hot': 'bg-orange-100 text-orange-700',
        'warm': 'bg-yellow-100 text-yellow-700',
        'lukewarm': 'bg-blue-100 text-blue-700',
        'cold': 'bg-gray-100 text-gray-700',
    };
    return classes[priority] || 'bg-gray-100 text-gray-700';
};

const getIconBackground = (item) => {
    const colors = {
        'call': 'bg-blue-100 dark:bg-blue-900/50',
        'email': 'bg-indigo-100 dark:bg-indigo-900/50',
        'email_received': 'bg-indigo-100 dark:bg-indigo-900/50',
        'meeting': 'bg-purple-100 dark:bg-purple-900/50',
        'visit': 'bg-pink-100 dark:bg-pink-900/50',
        'sms': 'bg-cyan-100 dark:bg-cyan-900/50',
        'note': 'bg-yellow-100 dark:bg-yellow-900/50',
        'task': 'bg-amber-100 dark:bg-amber-900/50',
        'reminder': 'bg-red-100 dark:bg-red-900/50',
        'contract': 'bg-emerald-100 dark:bg-emerald-900/50',
        'payment': 'bg-green-100 dark:bg-green-900/50',
    };
    const type = item.type === 'interaction' ? item.data.type : item.data.event;
    return colors[type] || 'bg-gray-100 dark:bg-gray-700';
};

const getIconColor = (item) => {
    const colors = {
        'call': 'text-blue-600',
        'email': 'text-indigo-600',
        'email_received': 'text-indigo-600',
        'meeting': 'text-purple-600',
        'visit': 'text-pink-600',
        'sms': 'text-cyan-600',
        'note': 'text-yellow-600',
        'task': 'text-amber-600',
        'reminder': 'text-red-600',
        'contract': 'text-emerald-600',
        'payment': 'text-green-600',
    };
    const type = item.type === 'interaction' ? item.data.type : item.data.event;
    return colors[type] || 'text-gray-600';
};

const getItemIcon = (item) => {
    if (item.type === 'interaction') {
        return item.data.icon;
    }
    return item.data.icon || 'info-circle';
};

const getItemTitle = (item) => {
    if (item.type === 'interaction') {
        return item.data.formatted_type;
    }
    return item.data.description || 'Activite';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const refreshTimeline = () => {
    router.reload({ only: ['timeline', 'stats'] });
};

const addQuickNote = async () => {
    if (!quickNote.value.trim()) return;
    submitting.value = true;

    try {
        const response = await fetch(route('tenant.crm.interactions.quick-note'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                entity_type: 'lead',
                entity_id: props.entity.id,
                content: quickNote.value,
            }),
        });

        if (response.ok) {
            quickNote.value = '';
            refreshTimeline();
        }
    } finally {
        submitting.value = false;
    }
};

const logCall = async () => {
    if (!callForm.value.outcome) return;
    submitting.value = true;

    try {
        const response = await fetch(route('tenant.crm.interactions.log-call'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                entity_type: 'lead',
                entity_id: props.entity.id,
                direction: callForm.value.direction,
                outcome: callForm.value.outcome,
                duration_seconds: callForm.value.duration_minutes ? callForm.value.duration_minutes * 60 : null,
                content: callForm.value.content,
            }),
        });

        if (response.ok) {
            callForm.value = { direction: 'outbound', outcome: '', duration_minutes: null, content: '' };
            refreshTimeline();
        }
    } finally {
        submitting.value = false;
    }
};

const scheduleTask = async () => {
    if (!scheduleForm.value.subject || !scheduleForm.value.scheduled_at) return;
    submitting.value = true;

    try {
        const response = await fetch(route('tenant.crm.interactions.schedule'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                entity_type: 'lead',
                entity_id: props.entity.id,
                ...scheduleForm.value,
            }),
        });

        if (response.ok) {
            scheduleForm.value = { type: 'task', subject: '', scheduled_at: '', priority: 'normal' };
            refreshTimeline();
        }
    } finally {
        submitting.value = false;
    }
};

const submitInteraction = async () => {
    submitting.value = true;

    try {
        const response = await fetch(route('tenant.crm.interactions.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                entity_type: 'lead',
                entity_id: props.entity.id,
                ...interactionForm.value,
            }),
        });

        if (response.ok) {
            showFullForm.value = false;
            interactionForm.value = {
                type: 'note',
                direction: 'outbound',
                subject: '',
                content: '',
                outcome: '',
                sentiment: '',
                scheduled_at: '',
                priority: 'normal',
            };
            refreshTimeline();
        }
    } finally {
        submitting.value = false;
    }
};

const completeInteraction = async (interaction) => {
    const outcome = prompt('Resultat (optionnel):');

    try {
        const response = await fetch(route('tenant.crm.interactions.complete', interaction.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ outcome }),
        });

        if (response.ok) {
            refreshTimeline();
        }
    } catch (error) {
        console.error('Complete error:', error);
    }
};

const editInteraction = (interaction) => {
    interactionForm.value = {
        type: interaction.type,
        direction: interaction.direction || 'outbound',
        subject: interaction.subject || '',
        content: interaction.content || '',
        outcome: interaction.outcome || '',
        sentiment: interaction.sentiment || '',
        scheduled_at: interaction.scheduled_at || '',
        priority: interaction.priority || 'normal',
    };
    showFullForm.value = true;
};

const deleteInteraction = async (interaction) => {
    if (!confirm('Supprimer cette interaction ?')) return;

    try {
        const response = await fetch(route('tenant.crm.interactions.destroy', interaction.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            refreshTimeline();
        }
    } catch (error) {
        console.error('Delete error:', error);
    }
};
</script>
