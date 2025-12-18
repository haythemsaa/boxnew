<template>
    <Head title="Créer une automatisation" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link :href="route('tenant.marketing.automation.index')"
                        class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux automatisations
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Créer une automatisation</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Configurez une séquence d'emails automatique
                    </p>
                </div>

                <form @submit.prevent="submit">
                    <!-- Basic Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations générales</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nom de l'automatisation *
                                </label>
                                <input v-model="form.name" type="text" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Ex: Bienvenue nouveau client">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Déclencheur *
                                </label>
                                <select v-model="form.trigger" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Sélectionner un déclencheur</option>
                                    <option v-for="trigger in triggers" :key="trigger.value" :value="trigger.value">
                                        {{ trigger.label }}
                                    </option>
                                </select>
                                <p v-if="selectedTrigger" class="mt-1 text-xs text-gray-500">
                                    {{ selectedTrigger.description }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Description
                                </label>
                                <textarea v-model="form.description" rows="2"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Description optionnelle de cette automatisation"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Steps -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Étapes de la séquence</h3>
                            <button type="button" @click="addStep"
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Ajouter une étape
                            </button>
                        </div>

                        <!-- Steps Timeline -->
                        <div class="space-y-6">
                            <div v-for="(step, index) in form.steps" :key="index"
                                class="relative pl-8 border-l-2 border-indigo-200 dark:border-indigo-800">
                                <!-- Timeline dot -->
                                <div class="absolute left-0 top-0 transform -translate-x-1/2 w-4 h-4 rounded-full bg-indigo-600 border-2 border-white dark:border-gray-800"></div>

                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 ml-4">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-indigo-600">Étape {{ index + 1 }}</span>
                                            <span v-if="index === 0" class="text-xs text-gray-500">Immédiat</span>
                                            <span v-else class="text-xs text-gray-500">
                                                Après {{ formatDelay(step.delay_minutes) }}
                                            </span>
                                        </div>
                                        <button v-if="form.steps.length > 1" type="button" @click="removeStep(index)"
                                            class="text-red-500 hover:text-red-700 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                Nom de l'étape
                                            </label>
                                            <input v-model="step.name" type="text" required
                                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                placeholder="Ex: Email de bienvenue">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                Délai (depuis l'étape précédente)
                                            </label>
                                            <div class="flex gap-2">
                                                <input v-model.number="step.delay_value" type="number" min="0"
                                                    class="w-20 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <select v-model="step.delay_unit"
                                                    class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <option value="minutes">Minutes</option>
                                                    <option value="hours">Heures</option>
                                                    <option value="days">Jours</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            Sujet de l'email
                                        </label>
                                        <input v-model="step.subject" type="text" required
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="Bienvenue chez {{company}}!">
                                    </div>

                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                                                Contenu de l'email
                                            </label>
                                            <button type="button" @click="showTemplates(index)"
                                                class="text-xs text-indigo-600 hover:text-indigo-700">
                                                Utiliser un template
                                            </button>
                                        </div>
                                        <textarea v-model="step.content" rows="6" required
                                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono"
                                            placeholder="Bonjour {{first_name}},

Bienvenue chez nous ! Nous sommes ravis de vous compter parmi nos clients.

Cordialement,
L'équipe"></textarea>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Variables disponibles: {{first_name}}, {{last_name}}, {{full_name}}, {{email}}, {{company}}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <button type="button" @click="sendTestEmail(index)"
                                            class="text-xs text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                            Envoyer un test
                                        </button>
                                        <button type="button" @click="previewEmail(index)"
                                            class="text-xs text-gray-600 hover:text-gray-700 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Prévisualiser
                                        </button>
                                    </div>
                                </div>

                                <!-- Arrow to next step -->
                                <div v-if="index < form.steps.length - 1"
                                    class="absolute left-0 bottom-0 transform -translate-x-1/2 translate-y-full">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paramètres</h3>

                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.is_active"
                                    class="rounded text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Activer immédiatement</span>
                                    <p class="text-sm text-gray-500">L'automatisation démarrera dès sa création</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <Link :href="route('tenant.marketing.automation.index')"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 inline-flex items-center gap-2">
                            <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ isSubmitting ? 'Création...' : 'Créer l\'automatisation' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Test Email Modal -->
        <Teleport to="body">
            <div v-if="showTestModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showTestModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 z-10">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Envoyer un email de test</h3>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Adresse email
                            </label>
                            <input v-model="testEmail" type="email" required
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="votre@email.com">
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showTestModal = false"
                                class="px-4 py-2 text-gray-600 hover:text-gray-800">
                                Annuler
                            </button>
                            <button type="button" @click="confirmSendTest"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Envoyer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    triggers: {
        type: Array,
        default: () => [],
    },
    templates: {
        type: Array,
        default: () => [],
    },
});

const isSubmitting = ref(false);
const showTestModal = ref(false);
const testEmail = ref('');
const testStepIndex = ref(0);

const form = reactive({
    name: '',
    description: '',
    trigger: '',
    is_active: false,
    steps: [
        {
            name: 'Email de bienvenue',
            subject: '',
            content: '',
            delay_value: 0,
            delay_unit: 'minutes',
            delay_minutes: 0,
        },
    ],
});

const selectedTrigger = computed(() =>
    props.triggers.find(t => t.value === form.trigger)
);

const formatDelay = (minutes) => {
    if (minutes < 60) return `${minutes} min`;
    if (minutes < 1440) return `${Math.floor(minutes / 60)} h`;
    return `${Math.floor(minutes / 1440)} jour(s)`;
};

const calculateDelayMinutes = (step) => {
    const value = step.delay_value || 0;
    switch (step.delay_unit) {
        case 'hours': return value * 60;
        case 'days': return value * 1440;
        default: return value;
    }
};

const addStep = () => {
    form.steps.push({
        name: `Étape ${form.steps.length + 1}`,
        subject: '',
        content: '',
        delay_value: 1,
        delay_unit: 'days',
        delay_minutes: 1440,
    });
};

const removeStep = (index) => {
    form.steps.splice(index, 1);
};

const showTemplates = (index) => {
    // TODO: Show template selection modal
};

const sendTestEmail = (index) => {
    testStepIndex.value = index;
    showTestModal.value = true;
};

const confirmSendTest = () => {
    if (!testEmail.value) return;

    router.post(route('tenant.marketing.automation.send-test'), {
        email: testEmail.value,
        step: form.steps[testStepIndex.value],
    }, {
        onSuccess: () => {
            showTestModal.value = false;
            testEmail.value = '';
        },
    });
};

const previewEmail = (index) => {
    const step = form.steps[index];
    const content = step.content
        .replace(/\{\{first_name\}\}/g, 'Jean')
        .replace(/\{\{last_name\}\}/g, 'Dupont')
        .replace(/\{\{full_name\}\}/g, 'Jean Dupont')
        .replace(/\{\{email\}\}/g, 'jean@example.com')
        .replace(/\{\{company\}\}/g, 'BoxiBox');

    alert(content);
};

// Update delay_minutes when delay_value or delay_unit changes
watch(() => form.steps, (steps) => {
    steps.forEach(step => {
        step.delay_minutes = calculateDelayMinutes(step);
    });
}, { deep: true });

const submit = () => {
    isSubmitting.value = true;

    // Ensure delay_minutes is calculated
    form.steps.forEach(step => {
        step.delay_minutes = calculateDelayMinutes(step);
    });

    router.post(route('tenant.marketing.automation.store'), form, {
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>
