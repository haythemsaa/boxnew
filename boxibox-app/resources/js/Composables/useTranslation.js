import { ref } from 'vue'

// Translations object
const translations = {
    fr: {
        menu: {
            dashboard: 'Tableau de bord',
            contracts: 'Contrats',
            invoices: 'Factures',
            customers: 'Clients',
            boxes: 'Box de stockage',
            payments: 'Paiements',
            reports: 'Rapports',
            settings: 'Paramètres',
            profile: 'Profil',
            logout: 'Déconnexion',
        },
        contracts: {
            title: 'Gestion des Contrats',
            create: 'Nouveau contrat',
            edit: 'Modifier le contrat',
            view: 'Voir le contrat',
            delete: 'Supprimer',
            list: 'Liste des contrats',
            status: 'Statut',
            startDate: 'Date de début',
            endDate: 'Date de fin',
            customer: 'Client',
            box: 'Box de stockage',
            price: 'Prix mensuel',
            sign: 'Signer le contrat',
            renew: 'Renouveler',
            terminate: 'Résilier',
            signed: 'Signé',
            pending: 'En attente de signature',
            active: 'Actif',
            expired: 'Expiré',
            terminated: 'Résilié',
            noContracts: 'Aucun contrat trouvé',
            confirmDelete: 'Êtes-vous sûr de vouloir supprimer ce contrat ?',
            successCreate: 'Contrat créé avec succès',
            successUpdate: 'Contrat mis à jour avec succès',
            successDelete: 'Contrat supprimé avec succès',
        },
        invoices: {
            title: 'Gestion des Factures',
            create: 'Nouvelle facture',
            edit: 'Modifier la facture',
            view: 'Voir la facture',
            delete: 'Supprimer',
            list: 'Liste des factures',
            invoiceNumber: 'Numéro de facture',
            amount: 'Montant',
            total: 'Total TTC',
            status: 'Statut',
            date: 'Date',
            dueDate: 'Échéance',
            paid: 'Payée',
            pending: 'En attente',
            partial: 'Partiellement payée',
            overdue: 'En retard',
            pay: 'Payer',
            download: 'Télécharger',
            send: 'Envoyer par email',
            recordPayment: 'Enregistrer un paiement',
            noInvoices: 'Aucune facture trouvée',
            confirmDelete: 'Êtes-vous sûr de vouloir supprimer cette facture ?',
            successCreate: 'Facture créée avec succès',
            successUpdate: 'Facture mise à jour avec succès',
            successDelete: 'Facture supprimée avec succès',
            successPayment: 'Paiement enregistré avec succès',
        },
        customers: {
            title: 'Gestion des Clients',
            create: 'Nouveau client',
            edit: 'Modifier le client',
            view: 'Voir le client',
            delete: 'Supprimer',
            list: 'Liste des clients',
            name: 'Nom',
            email: 'E-mail',
            phone: 'Téléphone',
            address: 'Adresse',
            city: 'Ville',
            postalCode: 'Code postal',
            country: 'Pays',
            company: 'Entreprise',
            type: 'Type',
            individual: 'Personne physique',
            companyType: 'Entreprise',
            status: 'Statut',
            active: 'Actif',
            inactive: 'Inactif',
            suspended: 'Suspendu',
            contractsCount: 'Nombre de contrats',
            noCustomers: 'Aucun client trouvé',
            confirmDelete: 'Êtes-vous sûr de vouloir supprimer ce client ?',
            successCreate: 'Client créé avec succès',
            successUpdate: 'Client mis à jour avec succès',
            successDelete: 'Client supprimé avec succès',
        },
        forms: {
            save: 'Enregistrer',
            cancel: 'Annuler',
            delete: 'Supprimer',
            edit: 'Modifier',
            add: 'Ajouter',
            remove: 'Retirer',
            submit: 'Soumettre',
            reset: 'Réinitialiser',
            search: 'Rechercher',
            filter: 'Filtrer',
            export: 'Exporter',
            import: 'Importer',
            loading: 'Chargement...',
            requiredField: 'Ce champ est obligatoire',
            invalidEmail: 'Adresse e-mail invalide',
            passwordMismatch: 'Les mots de passe ne correspondent pas',
            success: 'Opération réussie',
            error: 'Une erreur est survenue',
            confirm: 'Êtes-vous sûr ?',
            yes: 'Oui',
            no: 'Non',
        },
        notifications: {
            title: 'Notifications',
            invoiceDue: 'Facture due',
            contractExpiring: 'Contrat en expiration',
            paymentReceived: 'Paiement reçu',
            newContract: 'Nouveau contrat',
            noNotifications: 'Aucune notification',
            markRead: 'Marquer comme lue',
            markUnread: 'Marquer comme non lue',
        },
    },
}

const currentLanguage = ref('fr')

export const useTranslation = () => {
    const t = (key) => {
        const keys = key.split('.')
        let value = translations[currentLanguage.value]

        for (const k of keys) {
            value = value?.[k]
        }

        return value || key
    }

    const setLanguage = (lang) => {
        if (translations[lang]) {
            currentLanguage.value = lang
            localStorage.setItem('language', lang)
        }
    }

    const getLanguage = () => currentLanguage.value

    const availableLanguages = () => Object.keys(translations)

    return {
        t,
        setLanguage,
        getLanguage,
        availableLanguages,
    }
}

// Initialize language from localStorage or default to French
const savedLanguage = localStorage.getItem('language') || 'fr'
currentLanguage.value = savedLanguage
