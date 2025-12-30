import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

/**
 * Composable pour gérer l'onboarding utilisateur
 */
export function useOnboarding() {
    const page = usePage()

    // État de l'onboarding
    const isNewUser = computed(() => {
        const user = page.props.auth?.user
        if (!user) return false

        // Vérifier si l'utilisateur est nouveau (créé il y a moins de 7 jours)
        const createdAt = new Date(user.created_at)
        const daysSinceCreation = (Date.now() - createdAt.getTime()) / (1000 * 60 * 60 * 24)
        return daysSinceCreation < 7
    })

    const hasCompletedOnboarding = computed(() => {
        return localStorage.getItem('onboarding_status') === 'completed' ||
               localStorage.getItem('tutorial_welcome_shown') === 'true' ||
               page.props.auth?.user?.onboarding_completed
    })

    /**
     * Détermine si l'onboarding doit être affiché
     * RÈGLE: Seulement la PREMIÈRE fois, jamais après
     */
    const shouldShowOnboarding = computed(() => {
        // Si le tutoriel a déjà été montré, ne jamais l'afficher automatiquement
        const welcomeShown = localStorage.getItem('tutorial_welcome_shown')
        if (welcomeShown === 'true') {
            return false
        }

        const status = localStorage.getItem('onboarding_status')
        // Afficher seulement si pas de status (première visite) ou en cours
        return !status || status === 'in_progress'
    })

    // Étapes du tutoriel pour le tenant
    const tenantOnboardingSteps = [
        {
            id: 'welcome-dashboard',
            title: 'Bienvenue sur votre tableau de bord',
            description: 'C\'est votre centre de contrôle. Vous y trouverez un aperçu de votre activité : occupation des boxes, revenus, alertes et actions rapides.',
            target: '.dashboard-stats',
            position: 'bottom',
            tip: 'Consultez ce tableau de bord chaque jour pour suivre votre activité.'
        },
        {
            id: 'sidebar-navigation',
            title: 'Navigation principale',
            description: 'Utilisez le menu latéral pour accéder à toutes les fonctionnalités : Sites, Boxes, Clients, Contrats, Factures et plus encore.',
            target: '.sidebar-nav',
            position: 'right',
            tip: 'Vous pouvez réduire le menu en cliquant sur l\'icône hamburger.'
        },
        {
            id: 'create-site',
            title: 'Créer votre premier site',
            description: 'Un site représente un emplacement physique de stockage (entrepôt, bâtiment). Commençons par en créer un !',
            target: '[data-tour="create-site"]',
            position: 'bottom',
            action: {
                type: 'navigate',
                route: '/tenant/sites/create'
            },
            actionLabel: 'Créer un site'
        },
        {
            id: 'site-form',
            title: 'Remplir les informations du site',
            description: 'Entrez le nom, l\'adresse et les caractéristiques de votre site de stockage. Ces informations apparaîtront sur vos documents.',
            target: '.site-form',
            position: 'right',
            tip: 'Vous pouvez avoir plusieurs sites avec des tarifs différents.'
        },
        {
            id: 'boxes-section',
            title: 'Ajouter des boxes',
            description: 'Les boxes sont les unités de stockage que vous louez. Chaque box a un numéro, une taille et un tarif.',
            target: '[data-tour="boxes-menu"]',
            position: 'right',
            action: {
                type: 'navigate',
                route: '/tenant/boxes'
            },
            actionLabel: 'Voir les boxes'
        },
        {
            id: 'create-box',
            title: 'Créer des boxes',
            description: 'Cliquez sur "Nouveau box" pour ajouter vos unités de stockage. Vous pouvez créer plusieurs boxes en une fois.',
            target: '[data-tour="create-box"]',
            position: 'bottom',
            tip: 'Conseil : Nommez vos boxes de manière logique (A-01, A-02, B-01...).'
        },
        {
            id: 'customers-section',
            title: 'Gérer vos clients',
            description: 'La section Clients vous permet de gérer votre base de données clients avec leurs coordonnées et historique.',
            target: '[data-tour="customers-menu"]',
            position: 'right',
            action: {
                type: 'navigate',
                route: '/tenant/customers'
            },
            actionLabel: 'Voir les clients'
        },
        {
            id: 'contracts-section',
            title: 'Les contrats',
            description: 'Un contrat lie un client à un box pour une durée déterminée. Il génère automatiquement les factures.',
            target: '[data-tour="contracts-menu"]',
            position: 'right',
            tip: 'Les contrats peuvent être signés électroniquement par vos clients.'
        },
        {
            id: 'invoices-section',
            title: 'Facturation automatique',
            description: 'Les factures sont générées automatiquement selon les contrats. Vous pouvez les envoyer par email en un clic.',
            target: '[data-tour="invoices-menu"]',
            position: 'right'
        },
        {
            id: 'settings-section',
            title: 'Personnalisez votre espace',
            description: 'Dans les paramètres, configurez votre entreprise, logo, modèles d\'email et préférences de facturation.',
            target: '[data-tour="settings-menu"]',
            position: 'right',
            tip: 'N\'oubliez pas de configurer vos coordonnées bancaires pour le prélèvement.'
        },
        {
            id: 'help-center',
            title: 'Besoin d\'aide ?',
            description: 'Notre équipe support est là pour vous aider. Contactez-nous via le chat ou consultez notre base de connaissances.',
            target: '[data-tour="help-button"]',
            position: 'left'
        },
        {
            id: 'tour-complete',
            title: 'Vous êtes prêt !',
            description: 'Félicitations ! Vous avez découvert les fonctionnalités principales de BoxiBox. N\'hésitez pas à explorer et à nous contacter si vous avez des questions.',
            target: null,
            position: 'center'
        }
    ]

    // Étapes simplifiées pour le dashboard
    const dashboardQuickTour = [
        {
            id: 'stats-overview',
            title: 'Vue d\'ensemble',
            description: 'Ces cartes affichent vos indicateurs clés : taux d\'occupation, revenus du mois, clients actifs et alertes.',
            target: '.dashboard-stats',
            position: 'bottom'
        },
        {
            id: 'quick-actions',
            title: 'Actions rapides',
            description: 'Accédez rapidement aux actions les plus courantes : nouveau client, nouveau contrat, nouvelle facture.',
            target: '.quick-actions',
            position: 'bottom'
        },
        {
            id: 'recent-activity',
            title: 'Activité récente',
            description: 'Suivez les dernières actions effectuées sur votre compte : nouveaux contrats, paiements reçus, etc.',
            target: '.recent-activity',
            position: 'left'
        },
        {
            id: 'alerts-panel',
            title: 'Alertes et rappels',
            description: 'Ne manquez aucune échéance : factures impayées, contrats à renouveler, maintenance prévue.',
            target: '.alerts-panel',
            position: 'left'
        }
    ]

    // Étapes pour la création de contrat
    const contractCreationSteps = [
        {
            id: 'select-customer',
            title: 'Sélectionner le client',
            description: 'Choisissez un client existant ou créez-en un nouveau directement depuis ce formulaire.',
            target: '.customer-select',
            position: 'bottom'
        },
        {
            id: 'select-box',
            title: 'Choisir le box',
            description: 'Sélectionnez le box à louer. Seuls les boxes disponibles sont affichés.',
            target: '.box-select',
            position: 'bottom'
        },
        {
            id: 'contract-dates',
            title: 'Définir les dates',
            description: 'Indiquez la date de début et la durée du contrat. La date de fin est calculée automatiquement.',
            target: '.contract-dates',
            position: 'bottom'
        },
        {
            id: 'pricing',
            title: 'Tarification',
            description: 'Le prix est basé sur le tarif du box. Vous pouvez appliquer une remise si nécessaire.',
            target: '.pricing-section',
            position: 'bottom',
            tip: 'Les promotions actives sont appliquées automatiquement.'
        },
        {
            id: 'signature',
            title: 'Signature électronique',
            description: 'Activez la signature électronique pour que le client puisse signer en ligne.',
            target: '.signature-options',
            position: 'bottom'
        }
    ]

    // Obtenir les étapes selon le contexte
    const getStepsForContext = (context) => {
        switch (context) {
            case 'full':
                return tenantOnboardingSteps
            case 'dashboard':
                return dashboardQuickTour
            case 'contract':
                return contractCreationSteps
            default:
                return tenantOnboardingSteps
        }
    }

    // Marquer l'onboarding comme terminé
    const completeOnboarding = () => {
        localStorage.setItem('onboarding_status', 'completed')
        // Marquer aussi comme montré pour éviter tout affichage automatique futur
        localStorage.setItem('tutorial_welcome_shown', 'true')

        // Sauvegarder côté serveur si la route existe
        try {
            if (typeof route === 'function' && route().has('tenant.onboarding.complete')) {
                router.post(route('tenant.onboarding.complete'), {}, {
                    preserveState: true,
                    preserveScroll: true
                })
            }
        } catch (e) {
            console.log('Onboarding route not available')
        }
    }

    // Réinitialiser l'onboarding (pour les tests ou demande utilisateur)
    const resetOnboarding = () => {
        localStorage.removeItem('onboarding_status')
        localStorage.removeItem('onboarding_step')
        localStorage.removeItem('tutorial_welcome_shown')
        // Supprimer aussi les clés spécifiques aux tours
        const keysToRemove = []
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i)
            if (key && (key.startsWith('tutorial_') || key.startsWith('onboarding_'))) {
                keysToRemove.push(key)
            }
        }
        keysToRemove.forEach(key => localStorage.removeItem(key))
    }

    return {
        isNewUser,
        hasCompletedOnboarding,
        shouldShowOnboarding,
        tenantOnboardingSteps,
        dashboardQuickTour,
        contractCreationSteps,
        getStepsForContext,
        completeOnboarding,
        resetOnboarding
    }
}
