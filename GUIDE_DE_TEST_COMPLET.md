# BoxiBox - Guide de Test Complet 🧪

## 🎯 Objectif

Ce guide vous permet de tester TOUTES les fonctionnalités du système BoxiBox multi-tenant de manière systématique.

---

## ⚙️ Pré-requis

### 1. Serveur Actif
```bash
# Vérifier que le serveur Laravel tourne
http://127.0.0.1:8000
```

### 2. Base de Données
```bash
# Vérifier que la base de données est initialisée avec les seeders
# Les 4 users de test doivent exister
```

### 3. Comptes de Test

| Rôle | Email | Password |
|------|-------|----------|
| SuperAdmin | admin@boxibox.com | password |
| Tenant Owner | owner@demo-company.com | password |
| Tenant Admin | admin@demo-company.com | password |
| Tenant Staff | staff@demo-company.com | password |

---

## 📋 CHECKLIST COMPLÈTE DES TESTS

### Phase 1: Tests SuperAdmin

#### 1.1 Login SuperAdmin ✅
```
URL: http://127.0.0.1:8000/login
Email: admin@boxibox.com
Password: password
```

**Tests**:
- [ ] Page de login s'affiche correctement
- [ ] Login fonctionne avec les identifiants
- [ ] Redirection vers /superadmin/dashboard après login
- [ ] Message flash de bienvenue affiché

---

#### 1.2 Dashboard SuperAdmin ✅
```
URL: /superadmin/dashboard
```

**Tests**:
- [ ] 4 cartes statistiques affichées:
  - Total Tenants
  - Tenants Actifs
  - MRR (Monthly Recurring Revenue)
  - Taux de Churn
- [ ] Statistiques agrégées (Sites, Boxes, Contrats) affichées
- [ ] Graphique "Distribution par Plan" fonctionne
- [ ] Liste des tenants récents visible
- [ ] Boutons d'action rapide fonctionnent

---

#### 1.3 Gestion des Tenants ✅
```
URL: /superadmin/tenants
```

**Test 1: Liste des Tenants**
- [ ] Tableau affiche tous les tenants
- [ ] Colonnes: Nom, Plan, Sites, Boxes, Users, Statut, Actions
- [ ] Badges de statut colorés (Actif/Suspendu)
- [ ] Bouton "Nouveau Tenant" visible

**Test 2: Créer un Nouveau Tenant**
```
URL: /superadmin/tenants/create
```
- [ ] Formulaire de création s'affiche
- [ ] Champs: Nom, Slug, Email, Plan, Limites (Sites, Boxes, Users)
- [ ] Sélection du plan fonctionne
- [ ] Auto-génération du slug depuis le nom
- [ ] Validation fonctionne (essayer de soumettre vide)
- [ ] Création réussit → Redirection vers show
- [ ] Message de succès affiché

**Données de test**:
```
Nom: Test Company
Slug: test-company (auto-généré)
Email: test@test.com
Plan: Starter
Max Sites: 3
Max Boxes: 500
Max Users: 10
```

**Test 3: Voir Détails d'un Tenant**
```
URL: /superadmin/tenants/{tenant}
```
- [ ] Carte profil tenant visible
- [ ] Statistiques (Sites, Boxes, Users, Revenus) affichées
- [ ] Liste des utilisateurs du tenant
- [ ] Boutons d'action: Modifier, Suspendre/Activer, Supprimer
- [ ] Impersonate fonctionne (voir section 1.8)

**Test 4: Modifier un Tenant**
```
URL: /superadmin/tenants/{tenant}/edit
```
- [ ] Formulaire pré-rempli avec données actuelles
- [ ] Modification du nom fonctionne
- [ ] Changement de plan fonctionne
- [ ] Modification des limites fonctionne
- [ ] Mise à jour réussit → Redirection vers show
- [ ] Message de succès affiché

**Test 5: Activer/Suspendre un Tenant**
- [ ] Cliquer "Suspendre" → Statut change en "Suspendu"
- [ ] Badge devient rouge
- [ ] Cliquer "Activer" → Statut change en "Actif"
- [ ] Badge devient vert

**Test 6: Changer le Plan d'un Tenant**
- [ ] Cliquer "Changer de Plan"
- [ ] Sélectionner nouveau plan
- [ ] Confirmation → Plan modifié
- [ ] Message de succès

**Test 7: Supprimer un Tenant**
- [ ] Cliquer "Supprimer"
- [ ] Confirmation JavaScript apparaît
- [ ] Confirmer → Tenant supprimé
- [ ] Redirection vers liste tenants

---

#### 1.4 Gestion des Plans d'Abonnement ✅
```
URL: /superadmin/subscription-plans
```

**Test 1: Liste des Plans**
- [ ] 4 plans affichés (Free, Starter, Professional, Enterprise)
- [ ] Colonnes: Plan, Prix Mensuel, Prix Annuel, Limites, Statut, Actions
- [ ] Badges statut (Actif/Inactif)
- [ ] Bouton "Nouveau Plan" visible

**Test 2: Créer un Plan Personnalisé**
```
URL: /superadmin/subscription-plans/create
```
- [ ] Formulaire s'affiche
- [ ] Auto-génération slug fonctionne
- [ ] Auto-suggestion prix annuel (17% économie) fonctionne
- [ ] Checkboxes features fonctionnent
- [ ] Création réussit
- [ ] Message de succès

**Données de test**:
```
Nom: Custom Plan
Prix Mensuel: 149.99
Prix Annuel: 1499.99 (suggéré)
Sites: 20
Boxes: 5000
Users: 100
Features: floor_plans, advanced_reports, api_access
```

**Test 3: Voir Détails d'un Plan**
```
URL: /superadmin/subscription-plans/{plan}
```
- [ ] Informations du plan affichées
- [ ] 4 cartes stats: Total Abonnements, Actifs, Revenus Mensuel, Revenus Annuel
- [ ] Liste des tenants utilisant ce plan
- [ ] Boutons: Modifier, Activer/Désactiver

**Test 4: Modifier un Plan**
```
URL: /superadmin/subscription-plans/{plan}/edit
```
- [ ] Formulaire pré-rempli
- [ ] Modification prix fonctionne
- [ ] Modification limites fonctionne
- [ ] Modification features fonctionne
- [ ] Mise à jour réussit

**Test 5: Activer/Désactiver un Plan**
- [ ] Désactiver → Badge devient "Inactif"
- [ ] Activer → Badge devient "Actif"

**Test 6: Supprimer un Plan**
- [ ] Essayer de supprimer plan avec abonnements actifs → Erreur
- [ ] Supprimer plan sans abonnements → Succès

---

#### 1.5 Activity Logs ✅
```
URL: /superadmin/activity-logs
```

**Test 1: Liste Tous les Logs**
- [ ] 4 cartes stats affichées (Total, Aujourd'hui, Cette Semaine, Ce Mois)
- [ ] Tableau des logs avec colonnes: Date, Tenant, Utilisateur, Action, Entité, IP
- [ ] Badges colorés par type d'action
- [ ] Pagination fonctionne

**Test 2: Filtres Avancés**
- [ ] Filtre par Tenant fonctionne
- [ ] Filtre par Action fonctionne
- [ ] Filtre par Type d'Entité fonctionne
- [ ] Filtre par Date (From/To) fonctionne
- [ ] Bouton "Réinitialiser filtres" fonctionne

**Test 3: Voir Métadonnées**
- [ ] Cliquer sur une ligne → Métadonnées JSON s'affichent
- [ ] JSON formaté lisible
- [ ] Cliquer à nouveau → Métadonnées se cachent

**Test 4: Export CSV**
- [ ] Cliquer "Exporter CSV"
- [ ] Fichier CSV téléchargé
- [ ] Données correctes dans le fichier

**Test 5: Logs par Tenant (Timeline)**
```
URL: /superadmin/activity-logs/{tenant}
```
- [ ] Carte info tenant affichée (Nom, Plan, Statut)
- [ ] 4 mini stats pour ce tenant
- [ ] Timeline verticale avec icônes
- [ ] Couleurs différentes par type d'action
- [ ] Métadonnées expandables
- [ ] Filtres spécifiques au tenant fonctionnent

---

#### 1.6 Impersonate un Tenant ✅
```
Se faire passer pour un tenant pour tester son compte
```

**Test**:
- [ ] Depuis /superadmin/tenants/{tenant} → Cliquer "Se connecter comme"
- [ ] Connexion automatique en tant que Owner du tenant
- [ ] Redirection vers /tenant/dashboard
- [ ] Banner "Vous êtes connecté en tant que..." affiché (si implémenté)
- [ ] Tester fonctionnalités tenant (Dashboard, Team, Subscription)
- [ ] Se déconnecter → Retour au compte SuperAdmin

---

### Phase 2: Tests Tenant

#### 2.1 Login Tenant ✅
```
URL: http://127.0.0.1:8000/login
Email: owner@demo-company.com
Password: password
```

**Tests**:
- [ ] Login fonctionne
- [ ] Redirection vers /tenant/dashboard
- [ ] Message de bienvenue

---

#### 2.2 Dashboard Tenant ✅
```
URL: /tenant/dashboard
```

**Test 1: Boutons d'Action Rapide**
- [ ] 7 boutons circulaires affichés:
  - Nouveau Client
  - Nouveau Contrat
  - Nouvelle Réservation
  - Nouveau Box
  - Nouveau Site
  - Paramètres
  - Rapport
- [ ] Icônes visibles
- [ ] Hover rotation fonctionne
- [ ] Clic redirige vers page appropriée

**Test 2: Cartes Statistiques**
- [ ] 12 cartes affichées avec gradients
- [ ] Animations de révélation au scroll
- [ ] Compteurs animés (de 0 à valeur)
- [ ] Icônes Remix Icons correctes
- [ ] Couleurs cohérentes

**Test 3: Graphiques**
- [ ] Graphique "Évolution Revenus" (Chart.js) affiché
- [ ] Graphique "Occupation" affiché
- [ ] Tooltips au survol fonctionnent
- [ ] Données correctes

**Test 4: Endpoint AJAX Stats**
```
URL: /tenant/dashboard/stats (AJAX)
```
- [ ] Appel AJAX fonctionne
- [ ] JSON retourné correct
- [ ] Mise à jour stats en temps réel

---

#### 2.3 Settings Tenant ✅
```
URL: /tenant/settings
```

**Test 1: Navigation Onglets**
- [ ] 4 onglets affichés: Général, Branding, Notifications, Fonctionnalités
- [ ] Clic sur onglet → Change le contenu
- [ ] URL hash change (#general, #branding, etc.)
- [ ] Refresh page → Onglet actif conservé

**Test 2: Onglet Général**
- [ ] Formulaire pré-rempli avec données tenant
- [ ] Champs: Nom, Email, Téléphone, Adresse, Ville, Code Postal, Pays
- [ ] Selects: Timezone, Devise, Langue
- [ ] Modification fonctionne
- [ ] Message de succès

**Test 3: Onglet Branding**
- [ ] Upload logo fonctionne (PNG/JPG/SVG max 2MB)
- [ ] Upload favicon fonctionne (ICO/PNG max 512KB)
- [ ] Preview image affichée après upload
- [ ] Color pickers (Primary/Secondary) fonctionnent
- [ ] Preview couleurs en temps réel
- [ ] Textarea CSS personnalisé
- [ ] Enregistrement fonctionne
- [ ] Fichiers uploadés dans storage/app/public/tenants/{id}/branding

**Test 4: Onglet Notifications**
- [ ] 6 toggles affichés:
  - Email Notifications
  - SMS Notifications
  - Alertes Expiration Contrats
  - Rappels Paiements
  - Alerte Occupation Basse
  - Nouvelles Réservations
- [ ] Toggle ON/OFF fonctionne
- [ ] Enregistrement fonctionne
- [ ] Message de succès

**Test 5: Onglet Fonctionnalités**
- [ ] 6 toggles affichés:
  - Réservations en Ligne
  - Portail Client
  - Intégration Paiement
  - Facturation Automatique
  - Rappels SMS
  - Programme Fidélité
- [ ] Toggle fonctionne
- [ ] Enregistrement fonctionne

**Test 6: Avertissement Changements Non Sauvegardés**
- [ ] Modifier un champ
- [ ] Essayer de quitter → Alerte JavaScript
- [ ] Annuler → Reste sur page
- [ ] Confirmer → Quitte (changements perdus)

---

#### 2.4 Team Management ⭐ NOUVEAU
```
URL: /tenant/team
```

**Test 1: Liste de l'Équipe**
- [ ] 6 cartes stats affichées:
  - Total Utilisateurs
  - Owners
  - Admins
  - Managers
  - Staff
  - Invitations en Attente
- [ ] Tableau membres avec avatars colorés
- [ ] Badges rôles (Owner violet, Admin rouge, Manager orange, Staff bleu)
- [ ] Badge "Vous" sur utilisateur courant
- [ ] Statut actif/inactif visible
- [ ] Dernière connexion affichée
- [ ] Actions: Voir, Modifier, Supprimer (sauf Owner)

**Test 2: Section Invitations en Attente**
- [ ] Tableau invitations affiché
- [ ] Colonnes: Nom, Email, Rôle, Invité par, Expire le
- [ ] Badge expiration (rouge si <2 jours)
- [ ] Calcul jours restants correct
- [ ] Actions: Renvoyer, Annuler

**Test 3: Inviter un Nouveau Membre**
```
URL: /tenant/team/create
```
- [ ] Alerte limite utilisateurs affichée si applicable
- [ ] Formulaire avec champs: Nom, Email, Rôle
- [ ] Select Rôle (Admin, Manager, Staff) - pas d'option Owner
- [ ] Carte description des 3 rôles affichée:
  - Admin: Accès complet sauf suppression
  - Manager: Gestion sites et boxes
  - Staff: Consultation et tâches basiques
- [ ] Section "Que se passe-t-il ensuite?" avec checklist
- [ ] Validation email unique fonctionne
- [ ] Si limite atteinte → Erreur
- [ ] Invitation créée → Redirection liste
- [ ] Message succès "Invitation envoyée à..."

**Données de test**:
```
Nom: John Doe
Email: john.doe@test.com
Rôle: Manager
```

**Test 4: Voir Détails d'un Membre**
```
URL: /tenant/team/{team}
```
- [ ] Layout 2 colonnes (4/8)
- [ ] Colonne gauche:
  - Avatar large 120x120px avec initiales
  - Couleur selon rôle
  - Nom, email
  - Badge rôle avec icône
  - Badge statut (actif/inactif)
  - Bouton "Modifier Profil" (sauf si Owner)
- [ ] Colonne gauche - Carte Stats:
  - Membre depuis
  - Dernière connexion
  - Actions totales
- [ ] Colonne droite - Carte Informations:
  - Tableau avec toutes les infos
  - Dates formatées
- [ ] Colonne droite - Carte Activités Récentes:
  - Liste 10 dernières actions
  - Icônes dynamiques selon type
  - Badges colorés
  - Si vide → "Aucune activité"
- [ ] Zone de danger en bas:
  - Bouton suppression (si pas Owner et pas soi-même)
  - Confirmation JavaScript

**Test 5: Modifier un Membre**
```
URL: /tenant/team/{team}/edit
```
- [ ] Formulaire pré-rempli
- [ ] Si Owner → Alerte warning + champs disabled
- [ ] Section Profil: Nom, Email
- [ ] Section Rôle et Statut:
  - Select rôle (disabled si Owner)
  - Carte descriptive des 3 rôles
  - Toggle Actif/Inactif
  - Badge statut dynamique (JavaScript)
- [ ] Carte Infos Complémentaires:
  - Date inscription
  - Dernière connexion
- [ ] Note importante avec impacts modifications
- [ ] Boutons: Annuler (retour show), Enregistrer
- [ ] Modification fonctionne → Message succès

**Test 6: Supprimer un Membre**
- [ ] Essayer de supprimer Owner → Erreur "Impossible de supprimer le propriétaire"
- [ ] Essayer de se supprimer soi-même → Erreur
- [ ] Supprimer autre membre → Confirmation
- [ ] Confirmer → Suppression réussie
- [ ] Message "X a été supprimé de l'équipe"

**Test 7: Renvoyer une Invitation**
- [ ] Cliquer "Renvoyer" sur invitation
- [ ] Expiration prolongée de 7 jours
- [ ] Message "Invitation renvoyée à..."
- [ ] TODO: Vérifier email envoyé (quand implémenté)

**Test 8: Annuler une Invitation**
- [ ] Cliquer "Annuler" sur invitation
- [ ] Statut change en "cancelled"
- [ ] Invitation disparaît de la liste

---

#### 2.5 Subscription Management ⭐ NOUVEAU
```
URL: /tenant/subscription
```

**Test 1: Détails de l'Abonnement**
- [ ] Carte Plan Actuel avec gradient violet
- [ ] Nom du plan affiché
- [ ] Prix selon billing_cycle (mensuel/annuel)
- [ ] Date début et fin période
- [ ] Badge statut (Actif/Annulé/Expiré)
- [ ] Si période d'essai active:
  - Badge "Période d'essai"
  - Jours restants affichés
- [ ] Si abonnement annulé:
  - Message "Se termine le X"
  - Bouton "Réactiver" visible
- [ ] Si actif:
  - Bouton "Annuler l'abonnement" visible

**Test 2: Utilisation des Ressources**
- [ ] 3 cartes affichées:
  - Sites Utilisés (icône building)
  - Boxes Utilisés (icône archive)
  - Utilisateurs (icône team)
- [ ] Pour chaque carte:
  - Progress bar avec X/Y
  - Pourcentage calculé correct
  - Couleur selon %:
    - <70%: Success (vert)
    - 70-89%: Warning (orange)
    - >=90%: Danger (rouge)
- [ ] Si >90%: Alerte visuelle affichée

**Test 3: Fonctionnalités Incluses**
- [ ] Grid 2 colonnes
- [ ] Liste des features du plan
- [ ] Checkmarks verts
- [ ] Features lisibles

**Test 4: Historique de Facturation**
- [ ] Tableau 10 dernières factures
- [ ] Colonnes: Date, Plan, Cycle, Montant, Statut
- [ ] Badges statut colorés
- [ ] Bouton PDF pour factures payées (si implémenté)
- [ ] Si vide: "Aucune facture"

**Test 5: Annuler l'Abonnement**
- [ ] Cliquer "Annuler l'abonnement"
- [ ] Modal s'ouvre:
  - Titre "Confirmer l'annulation"
  - Textarea raison (optionnel)
  - Avertissement "Restera actif jusqu'à [date]"
  - Boutons: Annuler (fermer), Confirmer (danger)
- [ ] Saisir raison (optionnel)
- [ ] Confirmer
- [ ] Statut devient "Annulé"
- [ ] Date de fin affichée
- [ ] Message succès
- [ ] Bouton "Réactiver" apparaît

**Test 6: Réactiver l'Abonnement**
- [ ] Cliquer "Réactiver"
- [ ] Statut redevient "Actif"
- [ ] Date de fin retirée
- [ ] Raison annulation supprimée
- [ ] Message succès

---

#### 2.6 Changer de Plan ⭐ NOUVEAU
```
URL: /tenant/subscription/plans
```

**Test 1: Toggle Mensuel/Annuel**
- [ ] Toggle pills affiché en haut
- [ ] Badge "Économisez 17%" visible
- [ ] Cliquer Mensuel → Tous les prix passent en mensuel
- [ ] Cliquer Annuel → Tous les prix passent en annuel
- [ ] Badge économie affiché sur prix annuels
- [ ] Animation smooth lors du changement

**Test 2: Cartes de Plans**
- [ ] Grid 4 colonnes responsive (4→2→1)
- [ ] 4 plans affichés (Free, Starter, Professional, Enterprise)
- [ ] Plan actuel:
  - Badge "Plan Actuel"
  - Border highlight
  - Couleur différente
- [ ] Plan Professional:
  - Badge "Populaire"
  - Design mis en avant
- [ ] Pour chaque carte:
  - Nom et description
  - Prix dynamique (mensuel/annuel selon toggle)
  - Limites ressources (sites, boxes, users) avec icônes
  - Liste des features avec checkmarks
  - Bouton d'action
- [ ] Hover 3D effect fonctionne

**Test 3: Boutons d'Action**
- [ ] Plan actuel: Bouton "Plan Actuel" (disabled, gris)
- [ ] Autres plans: Bouton "Choisir ce Plan" (bleu)
- [ ] Cliquer bouton → Modal s'ouvre

**Test 4: Modal de Confirmation**
- [ ] Titre "Confirmer le changement de plan"
- [ ] Comparaison affichée:
  - Plan actuel: [nom]
  - Nouveau plan: [nom]
- [ ] Section Cycle de Facturation:
  - 2 radio buttons (Mensuel, Annuel)
  - Prix affiché pour chaque option
  - Badge économie sur annuel
- [ ] Résumé Prix:
  - Prix total calculé selon cycle sélectionné
  - Date prochaine facturation
- [ ] Avertissement: "Changement immédiat"
- [ ] Boutons: Annuler, Confirmer le Changement

**Test 5: Changement de Plan**
- [ ] Sélectionner nouveau plan
- [ ] Sélectionner cycle (mensuel ou annuel)
- [ ] Prix mis à jour dynamiquement
- [ ] Confirmer
- [ ] Ancien abonnement annulé
- [ ] Nouveau abonnement créé
- [ ] Période d'essai terminée (si active)
- [ ] Log d'activité créé
- [ ] Redirection vers /tenant/subscription
- [ ] Message succès "Votre abonnement a été changé vers [plan]"
- [ ] Plan actuel mis à jour

**Test 6: Validation**
- [ ] Essayer de changer vers plan actuel → Message info "Déjà sur ce plan"
- [ ] Essayer plan inactif → Erreur "Plan non disponible"

---

### Phase 3: Tests des Pages d'Erreur

#### 3.1 Page Tenant Suspendu
```
Pour tester: Suspendre un tenant via SuperAdmin, puis essayer de se connecter
```

**Tests**:
- [ ] Page affichée quand tenant suspendu
- [ ] Icône animée (pulse) visible
- [ ] Titre "Compte Suspendu"
- [ ] Description claire
- [ ] Section "Raisons possibles" avec liste
- [ ] Section "Que faire maintenant?" avec actions
- [ ] Carte contact avec email et téléphone support
- [ ] Bouton "Se Déconnecter" fonctionne

#### 3.2 Page Abonnement Expiré
```
Pour tester: Mettre ends_at dans le passé pour un tenant
```

**Tests**:
- [ ] Page affichée quand abonnement expiré
- [ ] Icône animée (shake) visible
- [ ] Titre "Abonnement Expiré"
- [ ] Alerte "Accès Limité" affichée
- [ ] Section "Fonctionnalités désactivées" avec liste
- [ ] Titre "Choisissez votre plan"
- [ ] 3 cartes plans (Starter, Professional, Enterprise):
  - Professional mis en avant (gradient + badge "Recommandé")
  - Prix affichés
  - Limites affichées
- [ ] Bouton "Renouveler Mon Abonnement" → Redirige vers /tenant/subscription/plans
- [ ] Lien "Contactez-nous" visible
- [ ] Bouton "Se Déconnecter" fonctionne

---

### Phase 4: Tests de Sécurité et Validations

#### 4.1 Protection des Routes

**Test 1: Routes SuperAdmin sans auth**
- [ ] Accéder /superadmin/dashboard sans login → Redirection login
- [ ] Accéder /superadmin/tenants sans login → Redirection login

**Test 2: Routes SuperAdmin avec compte Tenant**
- [ ] Se connecter comme Tenant
- [ ] Essayer /superadmin/dashboard → Erreur 403 ou redirection
- [ ] Essayer /superadmin/tenants → Erreur 403 ou redirection

**Test 3: Routes Tenant sans auth**
- [ ] Accéder /tenant/dashboard sans login → Redirection login
- [ ] Accéder /tenant/team sans login → Redirection login

**Test 4: Routes Tenant avec compte SuperAdmin**
- [ ] Se connecter comme SuperAdmin
- [ ] Essayer /tenant/dashboard → Erreur ou redirection
- [ ] Essayer /tenant/team → Erreur ou redirection

**Test 5: Protection Owner**
- [ ] Se connecter comme Admin (pas Owner)
- [ ] Essayer de modifier Owner → Erreur
- [ ] Essayer de supprimer Owner → Erreur
- [ ] Essayer de changer rôle Owner → Erreur

**Test 6: Isolation Multi-Tenant**
- [ ] Tenant A ne peut pas voir données Tenant B
- [ ] Tenant A ne peut pas modifier données Tenant B
- [ ] Chaque tenant voit uniquement ses propres:
  - Users
  - Sites
  - Boxes
  - Invitations
  - Logs

#### 4.2 Validations

**Test 1: Validations Email**
- [ ] Email invalide rejeté (ex: "test@")
- [ ] Email déjà utilisé rejeté
- [ ] Email requis si champ obligatoire

**Test 2: Validations Fichiers**
- [ ] Logo >2MB rejeté
- [ ] Favicon >512KB rejeté
- [ ] Type fichier incorrect rejeté (ex: .exe pour logo)
- [ ] Upload sans fichier → Pas d'erreur

**Test 3: Validations Limites**
- [ ] Inviter user quand limite atteinte → Erreur
- [ ] Créer site quand limite atteinte → Erreur (si implémenté)
- [ ] Créer box quand limite atteinte → Erreur (si implémenté)

**Test 4: Validations CSRF**
- [ ] Formulaire sans token CSRF → Erreur 419
- [ ] Token CSRF expiré → Erreur 419

---

### Phase 5: Tests de Performance

#### 5.1 Temps de Chargement

**Tests**:
- [ ] Dashboard SuperAdmin charge en <2s
- [ ] Dashboard Tenant charge en <2s
- [ ] Liste tenants charge en <3s
- [ ] Activity logs charge en <3s

#### 5.2 Pagination

**Tests**:
- [ ] Activity logs: 50 items par page
- [ ] Navigation pagination fonctionne
- [ ] Filtres conservés lors changement de page

#### 5.3 Requêtes N+1

**Tests**:
- [ ] Activer query log
- [ ] Charger liste tenants → Vérifier eager loading (users, subscriptions)
- [ ] Charger activity logs → Vérifier eager loading (tenant, user)

---

### Phase 6: Tests d'Intégration

#### 6.1 Workflow Complet Nouveau Tenant

1. **SuperAdmin: Créer Tenant**
   - [ ] Créer tenant "ABC Company"
   - [ ] Plan: Professional
   - [ ] Owner créé automatiquement

2. **Se connecter comme Owner ABC**
   - [ ] Login fonctionne
   - [ ] Dashboard affiché

3. **Configurer Settings**
   - [ ] Modifier nom entreprise
   - [ ] Upload logo
   - [ ] Changer couleurs

4. **Inviter Équipe**
   - [ ] Inviter 2 managers
   - [ ] Inviter 1 staff
   - [ ] Vérifier invitations en attente

5. **Vérifier Utilisation**
   - [ ] Aller sur /tenant/subscription
   - [ ] Vérifier users: 1/50 (Owner seulement)
   - [ ] Progress bar <10% verte

6. **Changer de Plan**
   - [ ] Aller sur /tenant/subscription/plans
   - [ ] Changer vers Enterprise
   - [ ] Vérifier nouveau plan actif
   - [ ] Vérifier limites: ∞

7. **Annuler Abonnement**
   - [ ] Annuler avec raison
   - [ ] Vérifier statut "Annulé"
   - [ ] Vérifier date fin

8. **Réactiver**
   - [ ] Réactiver abonnement
   - [ ] Vérifier statut "Actif"

9. **SuperAdmin: Vérifier Logs**
   - [ ] Aller sur /superadmin/activity-logs/{ABC}
   - [ ] Vérifier toutes les actions logged:
     - tenant_created
     - settings_updated
     - team_invitation_sent
     - subscription_changed
     - subscription_cancelled
     - subscription_resumed

#### 6.2 Workflow Suspension

1. **SuperAdmin: Suspendre Tenant**
   - [ ] Suspendre ABC Company
   - [ ] Badge devient "Suspendu"

2. **Tenant: Essayer de se connecter**
   - [ ] Se déconnecter
   - [ ] Se reconnecter comme Owner ABC
   - [ ] Middleware EnsureTenantActive détecte suspension
   - [ ] Redirection vers /tenant/suspended
   - [ ] Page suspended affichée

3. **SuperAdmin: Réactiver**
   - [ ] Réactiver ABC Company
   - [ ] Badge devient "Actif"

4. **Tenant: Se reconnecter**
   - [ ] Login fonctionne
   - [ ] Accès normal rétabli

---

## 📊 Checklist Récapitulative

### SuperAdmin
- [ ] Dashboard (Stats, Graphiques)
- [ ] Tenants (Liste, Create, Show, Edit, Delete, Activate, Suspend, Impersonate)
- [ ] Subscription Plans (Liste, Create, Show, Edit, Delete, Activate, Deactivate)
- [ ] Activity Logs (Liste, Filtres, Timeline, Export)

### Tenant
- [ ] Dashboard (Actions rapides, Stats, Graphiques)
- [ ] Settings (Général, Branding, Notifications, Features)
- [ ] Team (Liste, Invite, Show, Edit, Delete, Resend, Cancel)
- [ ] Subscription (Détails, Usage, Historique, Change Plan, Cancel, Resume)

### Pages d'Erreur
- [ ] Suspended
- [ ] Subscription Expired

### Sécurité
- [ ] Protection routes
- [ ] Validations
- [ ] Isolation multi-tenant
- [ ] CSRF protection

---

## 🐛 Rapport de Bugs

Si vous trouvez des bugs, notez:

**Format**:
```
Page: [URL]
Action: [Ce que vous avez fait]
Résultat Attendu: [Ce qui devrait se passer]
Résultat Obtenu: [Ce qui se passe réellement]
Erreur: [Message d'erreur si applicable]
```

**Exemple**:
```
Page: /tenant/team/create
Action: Soumettre formulaire avec email invalide
Résultat Attendu: Message "Email invalide"
Résultat Obtenu: Erreur 500
Erreur: "SQLSTATE[23000]: Integrity constraint violation"
```

---

## ✅ Validation Finale

Avant de déclarer le système prêt pour production:

- [ ] TOUS les tests de ce guide passent ✅
- [ ] Aucun bug bloquant
- [ ] Performance acceptable (<2s par page)
- [ ] Sécurité validée
- [ ] Isolation multi-tenant confirmée
- [ ] Mots de passe de production configurés
- [ ] Emails configurés (SendGrid/Mailgun)
- [ ] Stockage configuré (S3 ou local en production)
- [ ] HTTPS activé
- [ ] Backups configurés

---

**Bonne chance avec les tests! 🚀**
