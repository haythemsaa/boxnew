import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { createI18n } from 'vue-i18n';

// Import translations
import fr from '../../lang/fr.json';
import en from '../../lang/en.json';
import nl from '../../lang/nl.json';

// Create i18n instance
const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: document.documentElement.lang || 'fr',
    fallbackLocale: 'fr',
    messages: {
        fr,
        en,
        nl
    }
});

// Import global UI components
import Toast from './Components/UI/Toast.vue';
import Modal from './Components/UI/Modal.vue';
import Button from './Components/UI/Button.vue';
import Card from './Components/UI/Card.vue';
import Badge from './Components/UI/Badge.vue';
import Skeleton from './Components/UI/Skeleton.vue';

// Import form components
import TextInput from './Components/Form/TextInput.vue';
import SelectInput from './Components/Form/SelectInput.vue';
import TextareaInput from './Components/Form/TextareaInput.vue';

// Import transition components
import PageTransition from './Components/Transitions/PageTransition.vue';
import ListTransition from './Components/Transitions/ListTransition.vue';

// Import modern components
import AnimatedInput from './Components/Modern/AnimatedInput.vue';
import ModernButton from './Components/Modern/ModernButton.vue';
import ModernCard from './Components/Modern/ModernCard.vue';
import DynamicForm from './Components/Modern/DynamicForm.vue';
import ModernModal from './Components/Modern/ModernModal.vue';
import ModernTable from './Components/Modern/ModernTable.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Boxibox';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n);

        // Register global UI components
        app.component('UiToast', Toast);
        app.component('UiModal', Modal);
        app.component('UiButton', Button);
        app.component('UiCard', Card);
        app.component('UiBadge', Badge);
        app.component('UiSkeleton', Skeleton);

        // Register global form components
        app.component('FormInput', TextInput);
        app.component('FormSelect', SelectInput);
        app.component('FormTextarea', TextareaInput);

        // Register global transition components
        app.component('PageTransition', PageTransition);
        app.component('ListTransition', ListTransition);

        // Register modern components globally
        app.component('AnimatedInput', AnimatedInput);
        app.component('ModernButton', ModernButton);
        app.component('ModernCard', ModernCard);
        app.component('DynamicForm', DynamicForm);
        app.component('ModernModal', ModernModal);
        app.component('ModernTable', ModernTable);

        return app.mount(el);
    },
    progress: {
        color: '#2563eb',
        showSpinner: true,
    },
});
