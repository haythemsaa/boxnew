<template>
    <TenantLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nouveau Template Email</h1>
                    <p class="text-gray-600 dark:text-gray-400">Créez un template personnalisé pour vos emails</p>
                </div>
                <Link
                    :href="route('tenant.settings.email-templates.index')"
                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour
                </Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informations de base</h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nom du template *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Ex: Facture mensuelle"
                                required
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Catégorie *
                            </label>
                            <select
                                v-model="form.category"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                required
                            >
                                <option value="">Sélectionnez une catégorie</option>
                                <option v-for="(label, key) in categories" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Start from System Template -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-magic mr-1 text-purple-500"></i>
                            Démarrer depuis un modèle
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="(template, slug) in systemTemplates"
                                :key="slug"
                                type="button"
                                @click="loadSystemTemplate(slug, template)"
                                class="px-3 py-1.5 text-sm bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-800 transition"
                            >
                                {{ template.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Subject -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sujet de l'email</h2>

                    <input
                        v-model="form.subject"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        placeholder="Ex: Votre facture {{invoice_number}} est disponible"
                        required
                    />
                    <p class="text-sm text-gray-500 mt-1">
                        Utilisez <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">{{variable}}</code> pour les variables dynamiques
                    </p>
                </div>

                <!-- Content Editor -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Contenu de l'email</h2>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                :class="editorMode === 'visual' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                class="px-3 py-1 rounded-lg text-sm transition"
                                @click="editorMode = 'visual'"
                            >
                                <i class="fas fa-eye mr-1"></i>
                                Visuel
                            </button>
                            <button
                                type="button"
                                :class="editorMode === 'html' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                class="px-3 py-1 rounded-lg text-sm transition"
                                @click="editorMode = 'html'"
                            >
                                <i class="fas fa-code mr-1"></i>
                                HTML
                            </button>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <div v-if="editorMode === 'visual'" class="p-2 border-b border-gray-200 dark:border-gray-700 flex flex-wrap gap-1 bg-gray-50 dark:bg-gray-700/50">
                        <button type="button" @click="insertFormat('bold')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Gras">
                            <i class="fas fa-bold"></i>
                        </button>
                        <button type="button" @click="insertFormat('italic')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Italique">
                            <i class="fas fa-italic"></i>
                        </button>
                        <button type="button" @click="insertFormat('underline')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Souligné">
                            <i class="fas fa-underline"></i>
                        </button>
                        <div class="w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" @click="insertFormat('h1')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Titre 1">
                            H1
                        </button>
                        <button type="button" @click="insertFormat('h2')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Titre 2">
                            H2
                        </button>
                        <div class="w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        <button type="button" @click="insertFormat('ul')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Liste">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <button type="button" @click="insertFormat('link')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Lien">
                            <i class="fas fa-link"></i>
                        </button>
                        <button type="button" @click="insertFormat('button')" class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded" title="Bouton CTA">
                            <i class="fas fa-square"></i>
                        </button>
                        <div class="flex-1"></div>
                        <!-- Insert Variable -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="showVariableDropdown = !showVariableDropdown"
                                class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg text-sm hover:bg-blue-200"
                            >
                                <i class="fas fa-code mr-1"></i>
                                Insérer variable
                            </button>
                            <div
                                v-if="showVariableDropdown"
                                class="absolute right-0 mt-1 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10 max-h-64 overflow-y-auto"
                            >
                                <div v-for="(vars, group) in availableVariables" :key="group" class="border-b border-gray-200 dark:border-gray-700 last:border-0">
                                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 text-xs font-semibold text-gray-500 uppercase">
                                        {{ group }}
                                    </div>
                                    <button
                                        v-for="(desc, varName) in vars"
                                        :key="varName"
                                        type="button"
                                        @click="insertVariable(varName)"
                                        class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 text-sm"
                                    >
                                        <code class="text-blue-600 dark:text-blue-400">{{ varName }}</code>
                                        <span class="text-gray-500 ml-2">{{ desc }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Editor -->
                    <div class="p-4">
                        <div v-if="editorMode === 'visual'" class="grid md:grid-cols-2 gap-4">
                            <!-- Edit Area -->
                            <div>
                                <div
                                    ref="visualEditor"
                                    contenteditable="true"
                                    class="min-h-[400px] p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:outline-none prose dark:prose-invert max-w-none"
                                    @input="updateHtmlFromVisual"
                                    v-html="visualContent"
                                ></div>
                            </div>
                            <!-- Preview -->
                            <div>
                                <div class="text-sm text-gray-500 mb-2">Prévisualisation</div>
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden bg-white">
                                    <iframe
                                        :srcdoc="getWrappedHtml()"
                                        class="w-full h-[400px]"
                                        sandbox
                                    ></iframe>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <textarea
                                v-model="form.body_html"
                                rows="20"
                                class="w-full px-4 py-3 font-mono text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-900 text-green-400 focus:ring-2 focus:ring-blue-500"
                                placeholder="<!DOCTYPE html>..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Variables Used -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Variables utilisées</h2>
                    <div v-if="detectedVariables.length > 0" class="flex flex-wrap gap-2">
                        <span
                            v-for="variable in detectedVariables"
                            :key="variable"
                            class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-full text-sm font-mono"
                        >
                            {{ variable }}
                        </span>
                    </div>
                    <p v-else class="text-gray-500">Aucune variable détectée. Utilisez {{variable}} dans votre contenu.</p>
                </div>

                <!-- Options -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <label class="flex items-center gap-3">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-gray-900 dark:text-white">Activer ce template</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <Link
                        :href="route('tenant.settings.email-templates.index')"
                        class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Créer le template
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    categories: Object,
    systemTemplates: Object,
    availableVariables: Object,
});

const form = useForm({
    name: '',
    category: '',
    subject: '',
    body_html: '',
    body_text: '',
    variables: [],
    is_active: true,
});

const editorMode = ref('visual');
const visualEditor = ref(null);
const visualContent = ref('<p>Bonjour {{customer_name}},</p><p>Votre contenu ici...</p><p>Cordialement,<br>L\'équipe {{company_name}}</p>');
const showVariableDropdown = ref(false);

// Sync visual content with form
watch(visualContent, (newVal) => {
    form.body_html = getWrappedHtml();
});

onMounted(() => {
    form.body_html = getWrappedHtml();
});

const getWrappedHtml = () => {
    return `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        h1 { color: #2563eb; }
        h2 { color: #1e40af; }
        a { color: #2563eb; }
        .button { display: inline-block; background: #2563eb; color: white !important; padding: 12px 24px; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
${visualContent.value}
</body>
</html>`;
};

const updateHtmlFromVisual = () => {
    if (visualEditor.value) {
        visualContent.value = visualEditor.value.innerHTML;
    }
};

const detectedVariables = computed(() => {
    const content = form.subject + form.body_html;
    const matches = content.match(/\{\{([^}]+)\}\}/g) || [];
    return [...new Set(matches.map(m => m.replace(/[{}]/g, '')))];
});

watch(detectedVariables, (vars) => {
    form.variables = vars;
});

const loadSystemTemplate = (slug, template) => {
    form.name = template.name;
    form.category = template.category;
    form.subject = template.subject;
    form.variables = template.variables;

    // Set default content based on template type
    const defaultContents = {
        invoice_created: '<h1 style="color: #2563eb;">Nouvelle facture</h1><p>Bonjour {{customer_name}},</p><p>Une nouvelle facture a été créée sur votre compte.</p><p><a href="{{invoice_link}}" class="button">Voir la facture</a></p>',
        payment_received: '<h1 style="color: #16a34a;">Paiement reçu</h1><p>Bonjour {{customer_name}},</p><p>Nous confirmons la réception de votre paiement de {{amount}}.</p>',
        payment_reminder: '<h1 style="color: #dc2626;">Rappel de paiement</h1><p>Bonjour {{customer_name}},</p><p>La facture {{invoice_number}} de {{amount}} est en attente depuis {{days_overdue}} jours.</p><p><a href="{{payment_link}}" class="button">Payer maintenant</a></p>',
        welcome_customer: '<h1 style="color: #2563eb;">Bienvenue !</h1><p>Bonjour {{customer_name}},</p><p>Bienvenue chez {{company_name}} !</p><p><a href="{{portal_link}}" class="button">Accéder à mon espace</a></p>',
    };

    visualContent.value = defaultContents[slug] || '<p>Contenu à personnaliser...</p>';
};

const insertVariable = (varName) => {
    if (editorMode.value === 'visual' && visualEditor.value) {
        document.execCommand('insertText', false, `{{${varName}}}`);
        updateHtmlFromVisual();
    } else {
        form.body_html += `{{${varName}}}`;
    }
    showVariableDropdown.value = false;
};

const insertFormat = (format) => {
    if (!visualEditor.value) return;

    const selection = window.getSelection();
    const selectedText = selection.toString() || 'Texte';

    const formats = {
        bold: () => document.execCommand('bold'),
        italic: () => document.execCommand('italic'),
        underline: () => document.execCommand('underline'),
        h1: () => document.execCommand('formatBlock', false, 'h1'),
        h2: () => document.execCommand('formatBlock', false, 'h2'),
        ul: () => document.execCommand('insertUnorderedList'),
        link: () => {
            const url = prompt('URL du lien:', 'https://');
            if (url) document.execCommand('createLink', false, url);
        },
        button: () => {
            const url = prompt('URL du bouton:', '{{portal_link}}');
            if (url) {
                document.execCommand('insertHTML', false,
                    `<a href="${url}" style="display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">${selectedText || 'Cliquez ici'}</a>`
                );
            }
        },
    };

    if (formats[format]) {
        formats[format]();
        updateHtmlFromVisual();
    }
};

const submit = () => {
    form.body_html = getWrappedHtml();
    form.body_text = visualEditor.value?.innerText || '';
    form.post(route('tenant.settings.email-templates.store'));
};
</script>
