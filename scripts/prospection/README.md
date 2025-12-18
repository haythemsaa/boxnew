# BoxiBox - Kit de Prospection Automatise (GRATUIT)

## Vue d'ensemble

Ce kit permet de trouver et contacter automatiquement des prospects pour BoxiBox **sans aucun cout**.

```
Cout total: 0 EUR/mois
Leads potentiels: 50-150/jour
Emails envoyables: 300/jour (gratuit avec Brevo)
```

## Stack Technique

| Outil | Usage | Cout |
|-------|-------|------|
| Python | Scraping & enrichissement | Gratuit |
| API Gouv (annuaire-entreprises) | Source principale | Gratuit |
| OpenStreetMap Nominatim | Geolocalisation | Gratuit |
| Overpass API | POI self-stockage | Gratuit |
| Brevo/Sendinblue | Envoi emails | Gratuit (300/jour) |
| BoxiBox API | Stockage CRM | Inclus |

## Installation

```bash
# 1. Creer environnement virtuel
python -m venv venv
source venv/bin/activate  # Linux/Mac
venv\Scripts\activate     # Windows

# 2. Installer les dependances
pip install requests beautifulsoup4

# 3. Configurer Brevo (pour les emails)
export BREVO_SMTP_USER="votre-email@example.com"
export BREVO_SMTP_KEY="xsmtpsib-xxx"
```

## Usage - Workflow Complet

### Etape 1: Scraper les leads (5 min)

```bash
# Scraper les principales villes francaises
python scraper_self_stockage.py \
    --villes "Paris,Lyon,Marseille,Toulouse,Bordeaux,Nantes,Nice,Lille" \
    --output leads_bruts.csv

# Resultat attendu: 50-200 leads bruts
```

### Etape 2: Enrichir les leads (10 min)

```bash
# Ajouter sites web et emails
python enrichir_leads.py \
    --input leads_bruts.csv \
    --output leads_enrichis.csv

# Resultat: leads avec score et priorite (hot/warm/cold)
```

### Etape 3: Envoyer la campagne email

```bash
# Mode test (sans envoi)
python email_campaign.py --leads leads_enrichis.csv --template intro --dry-run

# Envoi reel (uniquement les leads qualifies score >= 50)
python email_campaign.py --leads leads_enrichis.csv --template intro
```

### Etape 4: Relances automatiques

```bash
# J+3: Premier follow-up
python email_campaign.py --leads leads_enrichis.csv --template followup_3j

# J+7: Etude de cas
python email_campaign.py --leads leads_enrichis.csv --template followup_7j

# J+14: Derniere chance
python email_campaign.py --leads leads_enrichis.csv --template last_chance
```

## Sources de Donnees

### 1. API Gouvernementale (Principale)
- **URL**: https://recherche-entreprises.api.gouv.fr
- **Donnees**: SIREN, adresse, code NAF (52.10B = entreposage)
- **Limite**: Aucune limite connue
- **Fiabilite**: Excellente (donnees officielles)

### 2. OpenStreetMap Nominatim
- **URL**: https://nominatim.openstreetmap.org
- **Donnees**: Noms, adresses, coordonnees GPS
- **Limite**: 1 requete/seconde
- **Fiabilite**: Bonne (donnees collaboratives)

### 3. Overpass API
- **URL**: https://overpass-api.de
- **Donnees**: POI detailles avec tags
- **Limite**: Variable (serveur parfois charge)
- **Fiabilite**: Bonne quand disponible

## Enrichissement des Leads

L'enrichisseur utilise plusieurs techniques:

1. **Base de donnees integree** des operateurs connus:
   - Shurgard, Une Piece en Plus, Homebox, Annexx, etc.
   - Sites web et emails pre-renseignes

2. **Recherche web automatique**:
   - DuckDuckGo API (gratuit, pas de cle)
   - Detection automatique des sites officiels

3. **Devinage d'email**:
   - Patterns communs: contact@, info@, accueil@
   - Validation basique du domaine

## Scoring des Leads

| Score | Priorite | Criteres |
|-------|----------|----------|
| 70-100 | HOT | Email + Site web + Telephone |
| 50-69 | WARM | Site web OU telephone |
| 0-49 | COLD | Uniquement nom + adresse |

## Templates d'Emails

4 templates inclus:

1. **intro** - Premier contact professionnel
2. **followup_3j** - Relance J+3 (chiffres cles)
3. **followup_7j** - Etude de cas J+7
4. **last_chance** - Offre speciale J+14

## Automatisation avec Cron

```bash
# Scraper tous les lundis a 6h
0 6 * * 1 /path/to/venv/bin/python /path/to/scraper_self_stockage.py --villes "Paris,Lyon" --output /path/to/leads_$(date +\%Y\%m\%d).csv

# Enrichir a 7h
0 7 * * 1 /path/to/venv/bin/python /path/to/enrichir_leads.py --input /path/to/leads_*.csv --output /path/to/enrichis.csv

# Envoyer les emails tous les jours a 9h (max 50/jour)
0 9 * * * /path/to/venv/bin/python /path/to/email_campaign.py --leads /path/to/enrichis.csv --template intro --limit 50
```

## Import dans BoxiBox

```bash
# Envoyer directement a l'API BoxiBox
python scraper_self_stockage.py \
    --villes "Paris" \
    --output leads.csv \
    --api-url "https://votre-boxibox.com" \
    --api-key "votre-cle-api"
```

## Configuration Brevo (Gratuit)

1. **Creer un compte**: https://www.brevo.com
2. **Aller dans**: Parametres > SMTP & API
3. **Activer SMTP** et copier les identifiants
4. **Verifier votre domaine** (SPF/DKIM) pour une meilleure delivrabilite

### Limites gratuites Brevo:
- 300 emails/jour
- Tracking des ouvertures
- Pas de carte bancaire requise

## Bonnes Pratiques Anti-Spam

1. **Limiter les envois**: Max 50-100 emails/jour au debut
2. **Personnaliser**: Toujours inclure le nom de l'entreprise
3. **Espacement**: 3 secondes minimum entre chaque email
4. **Opt-out**: Inclure un lien de desinscription
5. **SPF/DKIM**: Configurer pour votre domaine

## Comparaison avec Solutions Payantes

| Solution | Cout/mois | Leads/mois |
|----------|-----------|------------|
| **Ce script** | 0 EUR | ~1500 |
| Apollo.io | 99 EUR | 1000 |
| Hunter.io | 49 EUR | 500 |
| LinkedIn Sales Nav | 79 EUR | Variable |

## Workflow n8n (Optionnel)

Un workflow n8n gratuit est inclus (`n8n_workflow_gratuit.json`) pour automatiser:
- Rotation quotidienne des villes
- Scraping OpenStreetMap
- Sauvegarde dans BoxiBox
- Notification Slack

Pour l'utiliser:
1. Installer n8n (gratuit): https://n8n.io
2. Importer le fichier JSON
3. Configurer les variables d'environnement

## Structure des Fichiers

```
scripts/prospection/
├── scraper_self_stockage.py  # Scraper multi-sources
├── enrichir_leads.py         # Enrichissement gratuit
├── email_campaign.py         # Campagne email
├── n8n_workflow_gratuit.json # Workflow n8n
└── README.md                 # Ce fichier
```

## Exemple de Resultats

Apres un scraping de 8 villes:
```
Total leads uniques: 150
Leads HOT (score >= 70): 25
Leads WARM (score 50-69): 45
Leads COLD (score < 50): 80

Avec email: 40
Avec site web: 55
```

## Support

Pour toute question: contact@boxibox.fr
