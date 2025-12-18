# Guide Utilisateur BoxiBox
## Solution de Gestion de Self-Stockage

---

# Table des Matieres

1. [Introduction](#introduction)
2. [Tableau de Bord](#tableau-de-bord)
3. [Gestion des Sites](#gestion-des-sites)
4. [Gestion des Boxes](#gestion-des-boxes)
5. [Plan Interactif](#plan-interactif)
6. [Gestion des Clients](#gestion-des-clients)
7. [Prospects et Leads](#prospects-et-leads)
8. [Contrats](#contrats)
9. [Facturation](#facturation)
10. [Paiements](#paiements)
11. [Signature Electronique](#signature-electronique)
12. [Mandat SEPA](#mandat-sepa)
13. [Relances et Rappels](#relances-et-rappels)
14. [Tarification Dynamique](#tarification-dynamique)
15. [Reservations en Ligne](#reservations-en-ligne)
16. [Analytiques et Rapports](#analytiques-et-rapports)
17. [CRM et Marketing](#crm-et-marketing)
18. [Parametres](#parametres)

---

# Introduction

BoxiBox est une solution SaaS complete pour la gestion de centres de self-stockage. Elle permet de gerer l'ensemble de votre activite : sites, boxes, clients, contrats, facturation, et bien plus encore.

## Pour qui est BoxiBox ?

- **Proprietaires de self-stockage** : Gerez un ou plusieurs sites facilement
- **Operateurs multi-sites** : Vision consolidee de tous vos emplacements
- **Franchises** : Chaque franchisé a son espace independant
- **Investisseurs** : Suivez la rentabilite de vos actifs

## Avantages cles

- Interface intuitive et moderne
- Accessible depuis n'importe quel appareil
- Automatisation des taches repetitives
- Signature electronique integree
- Facturation et prelevement automatiques
- Analytiques en temps reel

---

# Tableau de Bord

## Description
Le tableau de bord est votre centre de controle principal. Il offre une vue d'ensemble instantanee de votre activite.

## Elements affiches

### Indicateurs principaux (KPIs)
| Indicateur | Description |
|------------|-------------|
| **Taux d'occupation** | Pourcentage de boxes loues vs disponibles |
| **Revenus du mois** | Total des factures du mois en cours |
| **Clients actifs** | Nombre de clients avec contrat en cours |
| **Boxes disponibles** | Nombre de boxes prets a la location |

### Graphiques
- **Evolution de l'occupation** : Courbe sur 12 mois
- **Revenus mensuels** : Histogramme comparatif
- **Repartition par taille** : Camembert des types de boxes

### Alertes et notifications
- Factures impayees (> 30 jours)
- Contrats arrivant a echeance
- Boxes en maintenance
- Nouveaux prospects

### Actions rapides
- Creer un nouveau client
- Ajouter un contrat
- Generer une facture
- Voir les impayés

## Script video (2 min)

```
SCENE 1 (0:00-0:15) - Introduction
"Bienvenue sur BoxiBox ! Le tableau de bord est votre centre
de controle. D'un seul coup d'oeil, visualisez les indicateurs
cles de votre activite."

SCENE 2 (0:15-0:45) - KPIs
"En haut, vos indicateurs principaux : taux d'occupation a 85%,
revenus du mois, clients actifs. Ces chiffres se mettent a jour
en temps reel."

SCENE 3 (0:45-1:15) - Graphiques
"Les graphiques vous montrent l'evolution sur 12 mois.
Identifiez les tendances, anticipez les periodes creuses."

SCENE 4 (1:15-1:45) - Alertes
"La section alertes vous previent des actions urgentes :
factures impayees, contrats a renouveler. Ne manquez plus
aucune echeance."

SCENE 5 (1:45-2:00) - Conclusion
"Les actions rapides vous permettent d'agir immediatement.
Votre gestion quotidienne n'a jamais ete aussi simple."
```

---

# Gestion des Sites

## Description
Un site represente un emplacement physique de stockage (entrepot, batiment, zone). Vous pouvez gerer plusieurs sites depuis un seul compte.

## Fonctionnalites

### Creer un site
1. Cliquez sur **Sites** dans le menu
2. Cliquez sur **Nouveau site**
3. Remplissez les informations :
   - **Nom** : Nom commercial du site
   - **Code** : Identifiant unique (ex: PARIS-01)
   - **Adresse** : Adresse complete
   - **Ville, Code postal, Pays**
   - **Telephone** : Contact du site
   - **Email** : Email de contact
   - **Horaires d'ouverture** : Pour l'affichage client
   - **Description** : Presentation du site

### Informations avancees
- **Coordonnees GPS** : Pour la geolocalisation
- **Superficie totale** : En metres carres
- **Nombre de niveaux** : Etages du batiment
- **Acces handicapes** : Oui/Non
- **Surveillance** : Type de securite
- **Climatisation** : Disponibilite

### Gestion multi-sites
- Vue consolidee de tous les sites
- Comparaison des performances
- Filtrage par site dans les rapports
- Tarification specifique par site

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"La gestion multi-sites vous permet de controler tous vos
emplacements depuis une interface unique."

SCENE 2 (0:20-0:50)
"Pour creer un site, renseignez ses informations : nom,
adresse, contact. Ces details apparaitront sur vos documents
et votre portail client."

SCENE 3 (0:50-1:10)
"Chaque site peut avoir ses propres tarifs, horaires et
caracteristiques. Parfait pour les operateurs multi-sites."

SCENE 4 (1:10-1:30)
"Comparez les performances de vos sites en un clic.
Identifiez les plus rentables et optimisez votre strategie."
```

---

# Gestion des Boxes

## Description
Les boxes sont les unites de stockage que vous louez a vos clients. Chaque box a un numero, une taille, un tarif et un statut.

## Fonctionnalites

### Creer un box
1. Allez dans **Boxes** > **Nouveau box**
2. Selectionnez le **Site**
3. Renseignez :
   - **Numero** : Identifiant unique (ex: A-01, B-12)
   - **Etage/Niveau** : Localisation dans le batiment
   - **Type** : Standard, Climatise, Securise, Drive
   - **Taille** : Dimensions (L x l x H)
   - **Surface** : En m²
   - **Volume** : En m³
   - **Prix mensuel HT** : Tarif de base

### Statuts des boxes
| Statut | Couleur | Description |
|--------|---------|-------------|
| **Disponible** | Vert | Pret a la location |
| **Occupe** | Rouge | Actuellement loue |
| **Reserve** | Orange | Reserve, en attente |
| **Maintenance** | Gris | En travaux/indisponible |
| **Bloque** | Noir | Bloque administrativement |

### Creation en masse
- Importez une liste CSV
- Creez plusieurs boxes d'un coup
- Dupliquez un box existant

### Caracteristiques optionnelles
- Acces 24/7
- Alarme individuelle
- Prise electrique
- Eclairage
- Ventilation
- Porte motorisee

## Script video (2 min)

```
SCENE 1 (0:00-0:20)
"Les boxes sont le coeur de votre activite. BoxiBox vous
permet de les gerer efficacement avec une vue claire
de leur disponibilite."

SCENE 2 (0:20-0:50)
"Creez un box en quelques clics : numero, taille, prix.
Le systeme calcule automatiquement le volume et suggere
un tarif optimal."

SCENE 3 (0:50-1:20)
"Les statuts en couleur vous indiquent instantanement
la disponibilite. Vert pour disponible, rouge pour occupe,
orange pour reserve."

SCENE 4 (1:20-1:45)
"Besoin de creer 50 boxes ? Utilisez l'import CSV ou la
creation en masse. Gagnez des heures de saisie manuelle."

SCENE 5 (1:45-2:00)
"Ajoutez des caracteristiques : acces 24/7, alarme,
climatisation. Valorisez vos boxes premium."
```

---

# Plan Interactif

## Description
Le plan interactif offre une vue visuelle de votre site de stockage. Visualisez l'occupation en temps reel et gerez vos boxes directement sur le plan.

## Fonctionnalites

### Visualisation
- Plan 2D de votre site
- Couleurs par statut (disponible, occupe, reserve)
- Zoom et navigation fluide
- Vue par etage/niveau

### Interaction
- Cliquez sur un box pour voir ses details
- Creez un contrat directement depuis le plan
- Changez le statut d'un box
- Affectez un client

### Editeur de plan
- Dessinez vos boxes sur le plan
- Importez un plan de masse (image)
- Positionnez les boxes par glisser-deposer
- Redimensionnez et faites pivoter

### Filtres
- Par taille de box
- Par disponibilite
- Par gamme de prix
- Par caracteristiques

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Le plan interactif revolutionne votre gestion quotidienne.
Visualisez votre site comme si vous y etiez."

SCENE 2 (0:20-0:45)
"Les boxes sont colores selon leur statut. D'un coup d'oeil,
identifiez les espaces disponibles."

SCENE 3 (0:45-1:10)
"Cliquez sur un box pour agir : voir le client, creer un
contrat, changer le statut. Tout est accessible en un clic."

SCENE 4 (1:10-1:30)
"Creez votre propre plan avec l'editeur integre.
Importez votre plan de masse et positionnez vos boxes."
```

---

# Gestion des Clients

## Description
La fiche client centralise toutes les informations de vos locataires : coordonnees, contrats, factures, historique de communication.

## Fonctionnalites

### Creer un client
**Informations personnelles :**
- Civilite, Nom, Prenom
- Email, Telephone, Mobile
- Adresse complete

**Informations professionnelles (optionnel) :**
- Societe
- SIRET/SIREN
- TVA intracommunautaire

**Preferences :**
- Langue de communication
- Mode de facturation
- Mode de paiement prefere

### Fiche client
- **Onglet General** : Coordonnees et informations
- **Onglet Contrats** : Liste des contrats actifs et passes
- **Onglet Factures** : Historique de facturation
- **Onglet Paiements** : Historique des reglements
- **Onglet Documents** : Pieces jointes (CNI, justificatifs)
- **Onglet Historique** : Journal des interactions

### Fonctions avancees
- **Fusion de clients** : Regroupez les doublons
- **Export Excel** : Exportez votre base clients
- **Import CSV** : Importez depuis un autre systeme
- **Etiquettes** : Classez vos clients par tags

## Script video (2 min)

```
SCENE 1 (0:00-0:20)
"Vos clients meritent une attention particuliere. BoxiBox
centralise toutes leurs informations en un seul endroit."

SCENE 2 (0:20-0:50)
"La fiche client regroupe coordonnees, contrats, factures
et historique. Plus besoin de chercher dans plusieurs fichiers."

SCENE 3 (0:50-1:20)
"Ajoutez des documents : carte d'identite, attestation
d'assurance. Tout est stocke de maniere securisee."

SCENE 4 (1:20-1:45)
"L'historique retrace chaque interaction : emails envoyes,
appels, modifications. Un suivi complet de la relation client."

SCENE 5 (1:45-2:00)
"Exportez votre base en Excel, importez depuis un autre
systeme. BoxiBox s'adapte a vos besoins."
```

---

# Prospects et Leads

## Description
Gerez vos prospects depuis le premier contact jusqu'a la signature. Un pipeline visuel vous aide a suivre chaque opportunite.

## Fonctionnalites

### Pipeline de vente
| Etape | Description |
|-------|-------------|
| **Nouveau** | Premier contact, a qualifier |
| **Qualifie** | Besoin identifie, budget ok |
| **Visite** | Visite du site programmee/effectuee |
| **Proposition** | Devis envoye |
| **Negociation** | En discussion sur les termes |
| **Gagne** | Contrat signe → converti en client |
| **Perdu** | Opportunite perdue |

### Creer un prospect
- Coordonnees completes
- Source (web, telephon, recommandation)
- Besoin exprime (taille, duree, budget)
- Date de besoin
- Notes et commentaires

### Actions
- Programmer un rappel
- Envoyer un email
- Creer un devis
- Convertir en client
- Programmer une visite

### Conversion automatique
Quand un prospect signe, il est automatiquement converti en client avec son historique conserve.

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Chaque prospect est une opportunite. BoxiBox vous aide
a ne plus en laisser passer."

SCENE 2 (0:20-0:50)
"Le pipeline visuel montre ou en est chaque prospect.
Glissez-deposez pour changer d'etape."

SCENE 3 (0:50-1:10)
"Programmez des rappels, envoyez des emails de relance.
Ne laissez plus un prospect sans reponse."

SCENE 4 (1:10-1:30)
"A la signature, le prospect devient client automatiquement.
Tout son historique est conserve."
```

---

# Contrats

## Description
Le contrat est l'element central qui lie un client a un box pour une duree determinee. Il definit les conditions de location et genere automatiquement les factures.

## Fonctionnalites

### Creer un contrat
1. Selectionnez le **Client**
2. Choisissez le **Box**
3. Definissez les dates :
   - Date de debut
   - Duree (1 mois, 3 mois, 6 mois, 1 an, indefini)
   - Date de fin (calculee ou manuelle)
4. Configurez la tarification :
   - Prix mensuel
   - Remise eventuelle
   - Depot de garantie
   - Frais de dossier
5. Options :
   - Assurance
   - Services additionnels
   - Conditions particulieres

### Cycle de vie du contrat
```
BROUILLON → EN ATTENTE SIGNATURE → ACTIF → EN COURS → TERMINE/RESILIE
```

### Documents generes
- Contrat de location (PDF)
- Conditions generales
- Etat des lieux d'entree
- Mandat SEPA (si prelevement)

### Renouvellement
- Alerte avant echeance (30/15/7 jours)
- Renouvellement automatique ou manuel
- Proposition de nouvelles conditions
- Historique des renouvellements

### Resiliation
- Calcul du preavis
- Etat des lieux de sortie
- Solde de tout compte
- Restitution du depot de garantie

## Script video (2:30 min)

```
SCENE 1 (0:00-0:20)
"Le contrat est la piece maitresse de votre activite.
BoxiBox automatise sa creation et son suivi."

SCENE 2 (0:20-0:50)
"Creez un contrat en quelques clics : selectionnez client
et box, definissez duree et tarif. Le contrat se genere
automatiquement."

SCENE 3 (0:50-1:20)
"La signature electronique permet a votre client de signer
a distance. Fini les allers-retours de documents papier."

SCENE 4 (1:20-1:50)
"A l'echeance, recevez une alerte. Proposez un renouvellement
en un clic ou ajustez les conditions."

SCENE 5 (1:50-2:15)
"En cas de resiliation, BoxiBox calcule le preavis,
planifie l'etat des lieux et solde le compte."

SCENE 6 (2:15-2:30)
"Tous vos contrats sont archives et accessibles.
Conformite juridique garantie."
```

---

# Facturation

## Description
Le module de facturation gere l'ensemble du cycle de vie des factures : generation, envoi, suivi des paiements et relances.

## Fonctionnalites

### Generation automatique
- Factures recurrentes selon les contrats
- Facturation au prorata (entree/sortie en cours de mois)
- Regroupement des factures par client

### Creer une facture manuelle
- Facture libre (hors contrat)
- Ajout de lignes personnalisees
- Remises et ajustements
- Notes et mentions

### Contenu de la facture
- Numero sequentiel unique
- Coordonnees emetteur/destinataire
- Periode de facturation
- Detail des prestations
- TVA et totaux
- Conditions de paiement
- Mentions legales

### Envoi
- **Email** : Envoi direct avec PDF joint
- **Courrier** : Generation pour envoi postal
- **Portail client** : Disponible dans l'espace client

### Suivi des paiements
| Statut | Description |
|--------|-------------|
| **Brouillon** | En cours de creation |
| **Emise** | Envoyee au client |
| **Partiellement payee** | Paiement partiel recu |
| **Payee** | Reglement complet |
| **En retard** | Echeance depassee |
| **Annulee** | Facture annulee |

### Facturation en masse
- Selectionnez plusieurs contrats
- Generez toutes les factures en un clic
- Envoyez-les par email groupé
- Gagnez des heures chaque mois

## Script video (2 min)

```
SCENE 1 (0:00-0:20)
"La facturation automatique vous fait gagner un temps
precieux. Plus de saisie manuelle, moins d'erreurs."

SCENE 2 (0:20-0:50)
"Chaque mois, les factures sont generees automatiquement
selon vos contrats. Verifiez et envoyez en un clic."

SCENE 3 (0:50-1:20)
"Vos factures sont conformes : numero sequentiel, mentions
legales, TVA. Pret pour votre comptabilite."

SCENE 4 (1:20-1:45)
"Suivez les paiements en temps reel. Les factures en retard
sont mises en evidence pour action rapide."

SCENE 5 (1:45-2:00)
"La facturation en masse traite des centaines de factures
en quelques secondes. Efficacite maximale."
```

---

# Paiements

## Description
Enregistrez et suivez tous les paiements de vos clients. Plusieurs modes de paiement sont supportes.

## Modes de paiement

| Mode | Description |
|------|-------------|
| **Virement** | Virement bancaire classique |
| **Carte bancaire** | Paiement en ligne (Stripe) |
| **Prelevement SEPA** | Prelevement automatique |
| **Cheque** | Cheque bancaire |
| **Especes** | Paiement en liquide |

### Enregistrer un paiement
1. Selectionnez la facture
2. Choisissez le mode de paiement
3. Saisissez le montant
4. Indiquez la date et reference
5. Validez

### Paiement partiel
- Enregistrez plusieurs paiements sur une facture
- Suivi du reste a payer
- Historique des reglements

### Prelevement automatique
- Configuration du mandat SEPA
- Generation des fichiers de prelevement
- Gestion des rejets
- Reconciliation automatique

### Remboursements
- Avoir et note de credit
- Remboursement partiel ou total
- Tracabilite complete

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Simplifiez la gestion des paiements. BoxiBox supporte
tous les modes de reglement."

SCENE 2 (0:20-0:50)
"Enregistrez un paiement en quelques clics. Le solde
client se met a jour instantanement."

SCENE 3 (0:50-1:10)
"Le prelevement automatique securise vos revenus.
Plus de relances, plus d'impayes."

SCENE 4 (1:10-1:30)
"En cas de remboursement, generez un avoir et tracez
l'operation. Transparence totale."
```

---

# Signature Electronique

## Description
La signature electronique permet a vos clients de signer leurs contrats a distance, de maniere legale et securisee.

## Fonctionnalites

### Processus de signature
1. Creez le contrat dans BoxiBox
2. Cliquez sur **Envoyer pour signature**
3. Le client recoit un email avec un lien securise
4. Il visualise le document et signe
5. Vous etes notifie de la signature
6. Le contrat signe est archive

### Avantages
- **Rapidite** : Signature en quelques minutes
- **Legalite** : Valeur juridique reconnue (eIDAS)
- **Tracabilite** : Horodatage et certificat
- **Ecologie** : Zero papier
- **Pratique** : Signature depuis mobile

### Securite
- Authentification du signataire
- Certificat de signature
- Document scelle et non modifiable
- Horodatage certifie
- Archivage securise

### Rappels automatiques
- Relance si non signe apres 24h
- Rappel a 48h puis 7 jours
- Notification d'expiration (30 jours)

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"La signature electronique accelere vos mises en location.
Vos clients signent ou qu'ils soient."

SCENE 2 (0:20-0:50)
"Envoyez le contrat par email. Le client ouvre le lien,
visualise le document et signe en un clic."

SCENE 3 (0:50-1:10)
"La signature est legale et securisee. Horodatage,
certificat, archivage : tout est conforme."

SCENE 4 (1:10-1:30)
"Fini les allers-retours de courrier. Un contrat peut
etre signe en moins de 5 minutes."
```

---

# Mandat SEPA

## Description
Le mandat SEPA autorise le prelevement automatique sur le compte bancaire du client. Securisez vos revenus avec des prelevements reguliers.

## Fonctionnalites

### Creation du mandat
1. Selectionnez le client
2. Saisissez les coordonnees bancaires (IBAN/BIC)
3. Generez le mandat PDF
4. Faites signer (papier ou electronique)
5. Activez le prelevement

### Informations requises
- Nom du titulaire du compte
- IBAN (International Bank Account Number)
- BIC (Bank Identifier Code)
- Type de paiement (recurrent/ponctuel)
- Reference unique du mandat (RUM)

### Gestion des prelevements
- Calendrier des prelevements
- Generation des fichiers XML SEPA
- Export pour votre banque
- Suivi des executions

### Gestion des rejets
- Notification en cas de rejet
- Motif du rejet (provision, opposition)
- Relance automatique
- Passage en paiement manuel si necessaire

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Le prelevement SEPA automatise vos encaissements.
Plus de factures impayees, revenus securises."

SCENE 2 (0:20-0:50)
"Creez un mandat en saisissant l'IBAN du client.
Le document est genere automatiquement pour signature."

SCENE 3 (0:50-1:10)
"Chaque mois, les prelevements sont executes automatiquement.
Vous recevez un rapport de statut."

SCENE 4 (1:10-1:30)
"En cas de rejet, vous etes alerte immediatement.
Agissez rapidement pour regulariser."
```

---

# Relances et Rappels

## Description
Automatisez les relances pour les factures impayees. Definissez des scenarios de relance adaptes a votre politique.

## Fonctionnalites

### Scenarios de relance
| Etape | Delai | Action |
|-------|-------|--------|
| **Rappel 1** | J+7 | Email de rappel amical |
| **Rappel 2** | J+15 | Email + SMS |
| **Relance 1** | J+30 | Lettre de relance |
| **Relance 2** | J+45 | Mise en demeure |
| **Contentieux** | J+60 | Blocage acces + procedure |

### Configuration
- Personnalisez les delais
- Editez les modeles d'email/courrier
- Activez/desactivez les SMS
- Definissez les actions automatiques

### Actions automatiques
- Envoi d'emails de relance
- Envoi de SMS
- Blocage de l'acces au box
- Alerte au gestionnaire
- Generation de la mise en demeure

### Suivi
- Historique des relances envoyees
- Statut de chaque relance
- Reponses des clients
- Taux de recouvrement

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Les impayes sont un defi. BoxiBox automatise les relances
pour maximiser votre recouvrement."

SCENE 2 (0:20-0:50)
"Definissez votre scenario : rappel amical, relance,
mise en demeure. Le systeme execute automatiquement."

SCENE 3 (0:50-1:10)
"Emails, SMS, courriers : tous les canaux sont disponibles.
Adaptez le ton a chaque etape."

SCENE 4 (1:10-1:30)
"Suivez le taux de recouvrement et optimisez votre
strategie. Reduisez vos impayes significativement."
```

---

# Tarification Dynamique

## Description
Optimisez vos revenus avec une tarification intelligente qui s'adapte a la demande, la saison et l'occupation.

## Fonctionnalites

### Regles de tarification
| Type | Description |
|------|-------------|
| **Base** | Tarif standard du box |
| **Saisonnier** | Variation selon la periode |
| **Occupation** | Hausse si forte demande |
| **Duree** | Remise pour engagement long |
| **Promotion** | Offres temporaires |

### Configuration
- Prix minimum et maximum
- Coefficient saisonnier par mois
- Seuil d'occupation pour hausse
- Remises selon duree d'engagement
- Codes promotionnels

### Exemples
```
Box 5m² - Prix base : 50€/mois

Ete (juillet-aout) : +15% → 57,50€
Occupation > 90% : +10% → 55€
Engagement 12 mois : -10% → 45€
Code BIENVENUE : -20% premier mois → 40€
```

### Simulation
- Testez vos regles avant activation
- Visualisez l'impact sur les revenus
- Comparez avec la tarification actuelle

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"La tarification dynamique maximise vos revenus.
Ajustez les prix selon la demande."

SCENE 2 (0:20-0:50)
"Definissez vos regles : saisonnalite, occupation,
promotions. BoxiBox calcule le prix optimal."

SCENE 3 (0:50-1:10)
"Proposez des remises pour les engagements longs.
Fidelisez vos clients tout en securisant vos revenus."

SCENE 4 (1:10-1:30)
"Simulez l'impact de vos regles avant de les activer.
Decisions basees sur les donnees."
```

---

# Reservations en Ligne

## Description
Permettez a vos clients de reserver un box directement sur votre site web, 24h/24.

## Fonctionnalites

### Widget de reservation
- Integrable sur votre site
- Design personnalisable
- Responsive (mobile-friendly)
- Multi-langues

### Parcours client
1. Choix de la taille de box
2. Selection de la date de debut
3. Choix du site (si multi-sites)
4. Visualisation du prix
5. Saisie des coordonnees
6. Paiement en ligne (acompte ou total)
7. Confirmation par email

### Configuration
- Boxes disponibles a la reservation
- Acompte requis (montant ou %)
- Delai de reservation minimum
- Conditions d'annulation
- Promotion automatique

### Gestion des reservations
- Liste des reservations en attente
- Confirmation manuelle ou automatique
- Conversion en contrat
- Gestion des annulations

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Vos clients peuvent reserver 24h/24. Le widget de
reservation s'integre a votre site en quelques minutes."

SCENE 2 (0:20-0:50)
"Le parcours est simple : taille, date, coordonnees,
paiement. Reservation confirmee en moins de 2 minutes."

SCENE 3 (0:50-1:10)
"Recevez une notification a chaque reservation.
Confirmez et preparez l'accueil du client."

SCENE 4 (1:10-1:30)
"Augmentez vos conversions en captant les clients
qui cherchent en dehors de vos horaires."
```

---

# Analytiques et Rapports

## Description
Prenez des decisions eclairees grace aux tableaux de bord analytiques et aux rapports detailles.

## Tableaux de bord

### Performance globale
- Chiffre d'affaires par periode
- Evolution de l'occupation
- Taux de rotation
- Revenu moyen par box

### Analyse clients
- Nouveaux clients vs fideles
- Duree moyenne de location
- Taux de retention
- Valeur client (LTV)

### Analyse financiere
- Encaissements par mode de paiement
- Age des creances
- Previsionnel de tresorerie
- Comparaison budgetaire

## Rapports

### Rapports pre-configures
- Rapport mensuel d'activite
- Etat des impayes
- Occupation par site/taille
- Performance commerciale

### Rapports personnalises
- Selectionnez vos indicateurs
- Filtrez par periode/site/type
- Exportez en PDF ou Excel
- Planifiez l'envoi automatique

### Export comptable
- FEC (Fichier des Ecritures Comptables)
- Export pour votre logiciel comptable
- Rapprochement bancaire

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Les donnees guident vos decisions. BoxiBox vous offre
des analytiques puissantes et accessibles."

SCENE 2 (0:20-0:50)
"Visualisez l'evolution de votre activite : occupation,
revenus, clients. Identifiez les tendances."

SCENE 3 (0:50-1:10)
"Generez des rapports en quelques clics. Exportez en
PDF pour vos reunions ou en Excel pour analyse."

SCENE 4 (1:10-1:30)
"Le FEC facilite votre comptabilite. Exportez vos
ecritures dans un format standardise."
```

---

# CRM et Marketing

## Description
Gerez la relation client et developpez votre activite avec les outils CRM et marketing integres.

## Fonctionnalites

### Segmentation clients
- Par anciennete
- Par valeur (CA genere)
- Par type de box
- Par comportement

### Campagnes email
- Editeur de newsletter
- Templates pre-congus
- Personnalisation dynamique
- Suivi des ouvertures/clics

### Campagnes SMS
- Messages courts personnalises
- Envoi en masse
- Programmation horaire
- Rapport de delivrabilite

### Fidelisation
- Programme de parrainage
- Points de fidelite
- Offres anniversaire
- Avantages clients fideles

### Satisfaction
- Enquetes de satisfaction
- Avis clients
- Score NPS
- Suivi des reclamations

## Script video (1:30 min)

```
SCENE 1 (0:00-0:20)
"Fidelisez vos clients et developpez votre activite
avec le CRM integre de BoxiBox."

SCENE 2 (0:20-0:50)
"Segmentez votre base et envoyez des communications
ciblees. Newsletter, SMS, offres personnalisees."

SCENE 3 (0:50-1:10)
"Le programme de parrainage transforme vos clients
en ambassadeurs. Recompensez les recommandations."

SCENE 4 (1:10-1:30)
"Mesurez la satisfaction avec les enquetes integrees.
Ameliorez continuellement votre service."
```

---

# Parametres

## Description
Configurez BoxiBox selon vos besoins : informations entreprise, modeles de documents, integrations, utilisateurs.

## Sections

### Informations entreprise
- Raison sociale et forme juridique
- Adresse du siege
- SIRET, TVA intracommunautaire
- Logo (pour documents)
- Coordonnees bancaires
- Mentions legales

### Modeles de documents
- Contrat de location
- Facture
- Devis
- Relance
- Conditions generales

### Utilisateurs et roles
| Role | Droits |
|------|--------|
| **Administrateur** | Acces complet |
| **Manager** | Gestion sans parametres |
| **Commercial** | Prospects et contrats |
| **Comptable** | Factures et paiements |
| **Lecteur** | Consultation uniquement |

### Integrations
- Comptabilite (Sage, QuickBooks, etc.)
- CRM externe
- API pour developpeurs
- Webhooks

### Notifications
- Alertes email
- Notifications push
- Rappels automatiques
- Rapports periodiques

## Script video (1 min)

```
SCENE 1 (0:00-0:20)
"Personnalisez BoxiBox selon vos besoins.
Parametres entreprise, utilisateurs, integrations."

SCENE 2 (0:20-0:40)
"Gerez les acces par role. Chaque utilisateur voit
uniquement ce dont il a besoin."

SCENE 3 (0:40-1:00)
"Connectez vos outils existants : comptabilite, CRM.
BoxiBox s'integre a votre ecosysteme."
```

---

# Annexe : Creation de Videos Promotionnelles

## Outils IA pour creer des videos

### 1. Synthesia (Recommande)
**Site :** synthesia.io
**Prix :** A partir de 30€/mois
**Avantages :**
- Avatars IA realistes qui parlent
- 120+ langues supportees
- Templates professionnels
- Aucune camera necessaire

**Comment l'utiliser :**
1. Copiez le script de ce document
2. Choisissez un avatar (presentateur virtuel)
3. Collez le texte, l'IA genere la video
4. Ajoutez des captures d'ecran de BoxiBox
5. Exportez en HD

### 2. HeyGen
**Site :** heygen.com
**Prix :** A partir de 24$/mois
**Avantages :**
- Avatars tres realistes
- Clonage de voix
- Multi-langues
- API disponible

### 3. Pictory
**Site :** pictory.ai
**Prix :** A partir de 19$/mois
**Avantages :**
- Transforme du texte en video
- Ajoute automatiquement des visuels
- Voix off IA
- Ideal pour tutoriels

### 4. InVideo AI
**Site :** invideo.io
**Prix :** Gratuit (limite) / 25$/mois
**Avantages :**
- Templates marketing
- Stock videos/images inclus
- Edition facile
- Export sans filigrane (payant)

### 5. Lumen5
**Site :** lumen5.com
**Prix :** Gratuit (limite) / 29$/mois
**Avantages :**
- Transforme articles en videos
- IA suggere les visuels
- Branding personnalise
- Ideal pour reseaux sociaux

## Processus de creation recommande

### Etape 1 : Preparation (30 min)
- Choisissez la rubrique a presenter
- Utilisez le script de ce document
- Preparez les captures d'ecran de BoxiBox

### Etape 2 : Creation avec Synthesia (20 min)
1. Connectez-vous a Synthesia
2. Creez un nouveau projet
3. Choisissez un avatar professionnel
4. Collez le script
5. Ajoutez les captures d'ecran en incrustation
6. Ajoutez le logo BoxiBox en watermark
7. Generez la video

### Etape 3 : Post-production (10 min)
- Ajoutez une intro/outro avec le logo
- Inserez une musique de fond
- Ajoutez des sous-titres
- Exportez en 1080p

## Structure type d'une video

```
[0:00-0:05] INTRO
Logo BoxiBox + Titre de la rubrique

[0:05-0:15] ACCROCHE
Probleme que resout cette fonctionnalite

[0:15-1:30] DEMONSTRATION
Montrer la fonctionnalite en action
(captures d'ecran + narration)

[1:30-1:50] AVANTAGES
3 benefices cles de cette fonctionnalite

[1:50-2:00] OUTRO
Logo + Call-to-action "Essayez BoxiBox gratuitement"
```

## Conseils pour des videos efficaces

### Visuels
- Utilisez des captures d'ecran HD
- Montrez l'interface en action
- Ajoutez des fleches et annotations
- Gardez un style coherent

### Audio
- Voix claire et professionnelle
- Musique de fond discrete
- Pas de jargon technique
- Rythme dynamique

### Duree
- **Teaser reseaux sociaux :** 15-30 secondes
- **Tutoriel rapide :** 1-2 minutes
- **Demo complete :** 3-5 minutes
- **Webinaire :** 20-45 minutes

### Distribution
- **YouTube :** Videos longues, tutoriels
- **LinkedIn :** Videos professionnelles
- **Instagram/TikTok :** Clips courts
- **Site web :** Page produit
- **Email :** Lien vers la video

---

# Ressources complementaires

## Fichiers fournis avec ce guide
- Scripts video pour chaque rubrique
- Modeles de presentation
- Checklist de demarrage
- FAQ utilisateurs

## Support
- **Email :** support@boxibox.fr
- **Chat :** Disponible dans l'application
- **Base de connaissances :** docs.boxibox.fr
- **Formation :** Sur demande

---

*Document genere pour BoxiBox - Version 1.0*
*Derniere mise a jour : Decembre 2024*
