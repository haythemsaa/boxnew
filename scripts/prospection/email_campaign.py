#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
BoxiBox - Campagne Email Automatique (GRATUIT avec Brevo)
Envoie des emails de prospection aux leads scrapes.

Usage:
    python email_campaign.py --leads leads.csv --template intro

Cout: 0 EUR (300 emails/jour gratuit avec Brevo)
"""

import sys
import io

# Fix Windows console encoding
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

import csv
import json
import time
import argparse
from datetime import datetime
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import os

# Configuration SMTP (Brevo gratuit)
SMTP_CONFIG = {
    'host': 'smtp-relay.brevo.com',  # ou smtp-relay.sendinblue.com
    'port': 587,
    'username': os.getenv('BREVO_SMTP_USER', 'votre-email@example.com'),
    'password': os.getenv('BREVO_SMTP_KEY', 'votre-cle-smtp'),
}

# Email expéditeur
FROM_EMAIL = 'contact@boxibox.fr'
FROM_NAME = 'BoxiBox - Solutions Self-Stockage'

# Templates d'emails
EMAIL_TEMPLATES = {
    'intro': {
        'subject': 'Digitalisez {company} - Solution BoxiBox',
        'body': """Bonjour,

Je me permets de vous contacter car j'ai découvert votre centre de self-stockage {company}.

BoxiBox est la solution SaaS française #1 pour les opérateurs de self-stockage. Nous aidons des centaines de centres à :

✅ Automatiser la facturation et les relances (-15h/semaine)
✅ Proposer la réservation en ligne 24/7 (+35% de conversion)
✅ Augmenter le taux d'occupation (+23% en moyenne)
✅ Gérer les accès avec des codes numériques
✅ Analyser les performances avec des tableaux de bord

🎁 Offre découverte : Essai gratuit 14 jours + démo personnalisée

Seriez-vous disponible pour un appel de 15 minutes cette semaine ?

Vous pouvez réserver votre créneau directement ici : https://boxibox.fr/demo

Cordialement,

L'équipe BoxiBox
www.boxibox.fr

---
PS: Nous avons aidé +200 centres en France à augmenter leur chiffre d'affaires de 18% en moyenne."""
    },

    'followup_3j': {
        'subject': 'Re: Digitalisez {company} - Avez-vous 5 minutes ?',
        'body': """Bonjour,

Je me permets de revenir vers vous suite à mon précédent email.

Je comprends que vous êtes occupé(e), alors voici 3 chiffres qui pourraient vous intéresser :

📊 +23% de taux d'occupation en moyenne
⏱️ 15 heures gagnées par semaine sur l'administratif
💰 ROI positif dès le 3ème mois

Un rapide appel de 10 minutes suffit pour voir si BoxiBox peut vous aider.

Quand seriez-vous disponible ?

Cordialement,
L'équipe BoxiBox"""
    },

    'followup_7j': {
        'subject': 'Étude de cas : Comment StoragePlus a augmenté son CA de 28%',
        'body': """Bonjour,

Je voulais partager avec vous l'histoire de StoragePlus Paris, un centre de 150 boxes similaire au vôtre.

Avant BoxiBox :
❌ Gestion manuelle des contrats et factures
❌ Taux d'occupation de 72%
❌ 20h/semaine sur l'administratif

Après 6 mois avec BoxiBox :
✅ Taux d'occupation de 95%
✅ +28% de chiffre d'affaires
✅ 5h/semaine seulement sur l'admin

"BoxiBox nous a permis de nous concentrer sur le développement commercial plutôt que sur la paperasse." - Marie D., Gérante

Voulez-vous obtenir les mêmes résultats pour {company} ?

👉 Réservez votre démo gratuite : https://boxibox.fr/demo

Cordialement,
L'équipe BoxiBox"""
    },

    'last_chance': {
        'subject': '[Dernière relance] Offre spéciale pour {company}',
        'body': """Bonjour,

C'est mon dernier email, promis !

Je voulais vous informer que nous offrons exceptionnellement :

🎁 3 MOIS GRATUITS (au lieu de 1) pour tout nouvel abonnement cette semaine

Cette offre est valable uniquement pour les 10 prochains inscrits.

Si la gestion de votre centre vous prend trop de temps, ou si vous souhaitez augmenter votre taux d'occupation, BoxiBox est fait pour vous.

👉 Dernière chance : https://boxibox.fr/demo?promo=3MOIS

Cordialement,
L'équipe BoxiBox

PS: Même si ce n'est pas le bon moment, n'hésitez pas à me répondre pour qu'on en reparle plus tard."""
    }
}


class EmailCampaign:
    def __init__(self, smtp_config: dict):
        self.smtp_config = smtp_config
        self.sent_count = 0
        self.error_count = 0

    def load_leads(self, filename: str) -> list:
        """Charge les leads depuis un fichier CSV"""
        leads = []

        with open(filename, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            for row in reader:
                if row.get('email') and '@' in row.get('email', ''):
                    leads.append(row)

        return leads

    def personalize_email(self, template: dict, lead: dict) -> tuple:
        """Personnalise l'email avec les données du lead"""
        subject = template['subject'].format(
            company=lead.get('name', lead.get('company', 'votre centre')),
            **lead
        )

        body = template['body'].format(
            company=lead.get('name', lead.get('company', 'votre centre')),
            first_name=lead.get('first_name', ''),
            **{k: v for k, v in lead.items() if v}
        )

        return subject, body

    def send_email(self, to_email: str, subject: str, body: str) -> bool:
        """Envoie un email via SMTP"""
        try:
            msg = MIMEMultipart('alternative')
            msg['Subject'] = subject
            msg['From'] = f'{FROM_NAME} <{FROM_EMAIL}>'
            msg['To'] = to_email

            # Version texte
            msg.attach(MIMEText(body, 'plain', 'utf-8'))

            # Connexion SMTP
            with smtplib.SMTP(self.smtp_config['host'], self.smtp_config['port']) as server:
                server.starttls()
                server.login(self.smtp_config['username'], self.smtp_config['password'])
                server.send_message(msg)

            return True

        except Exception as e:
            print(f"    ✗ Erreur envoi à {to_email}: {str(e)[:50]}")
            return False

    def run_campaign(self, leads: list, template_name: str, dry_run: bool = False):
        """Lance une campagne email"""
        template = EMAIL_TEMPLATES.get(template_name)
        if not template:
            print(f"❌ Template '{template_name}' non trouvé")
            return

        print(f"\n📧 Campagne: {template_name}")
        print(f"   Leads: {len(leads)}")
        print(f"   Mode: {'DRY RUN (pas d\'envoi)' if dry_run else 'ENVOI RÉEL'}")
        print("-" * 50)

        for i, lead in enumerate(leads):
            email = lead.get('email')
            if not email:
                continue

            # Skip low score leads
            score = int(lead.get('score', 50))
            if score < 40:
                continue

            subject, body = self.personalize_email(template, lead)

            print(f"[{i+1}/{len(leads)}] {lead.get('name', 'N/A')} ({email})")

            if dry_run:
                print(f"    📝 Subject: {subject[:50]}...")
            else:
                if self.send_email(email, subject, body):
                    self.sent_count += 1
                    print(f"    ✓ Envoyé")
                else:
                    self.error_count += 1

            # Rate limiting (important pour éviter le spam flag)
            time.sleep(3)  # 3 secondes entre chaque email

            # Pause plus longue tous les 50 emails
            if (i + 1) % 50 == 0:
                print(f"\n⏸️  Pause de 60 secondes (anti-spam)...")
                time.sleep(60)

        print(f"\n📊 Résultat:")
        print(f"   Envoyés: {self.sent_count}")
        print(f"   Erreurs: {self.error_count}")


def setup_brevo_instructions():
    """Affiche les instructions pour configurer Brevo gratuit"""
    print("""
╔═══════════════════════════════════════════════════════════════════╗
║           CONFIGURATION BREVO (GRATUIT - 300 emails/jour)          ║
╠═══════════════════════════════════════════════════════════════════╣
║                                                                     ║
║  1. Créer un compte gratuit sur https://www.brevo.com              ║
║                                                                     ║
║  2. Aller dans Paramètres > SMTP & API                             ║
║                                                                     ║
║  3. Activer SMTP et noter :                                        ║
║     - Serveur: smtp-relay.brevo.com                                ║
║     - Port: 587                                                    ║
║     - Login: votre email                                           ║
║     - Mot de passe: clé SMTP générée                               ║
║                                                                     ║
║  4. Configurer les variables d'environnement :                     ║
║                                                                     ║
║     export BREVO_SMTP_USER="votre-email@example.com"               ║
║     export BREVO_SMTP_KEY="xsmtpsib-xxx-xxx"                       ║
║                                                                     ║
║  5. Vérifier votre domaine expéditeur (SPF/DKIM)                   ║
║                                                                     ║
║  Limite gratuite: 300 emails/jour                                  ║
║                                                                     ║
╚═══════════════════════════════════════════════════════════════════╝
""")


def main():
    parser = argparse.ArgumentParser(description='Campagne email de prospection')
    parser.add_argument('--leads', type=str, required=True, help='Fichier CSV des leads')
    parser.add_argument('--template', type=str, default='intro',
                        choices=['intro', 'followup_3j', 'followup_7j', 'last_chance'],
                        help='Template à utiliser')
    parser.add_argument('--dry-run', action='store_true', help='Mode test sans envoi')
    parser.add_argument('--setup', action='store_true', help='Afficher les instructions de config')
    parser.add_argument('--limit', type=int, default=300, help='Nombre max d\'emails à envoyer')

    args = parser.parse_args()

    if args.setup:
        setup_brevo_instructions()
        return

    print("=" * 50)
    print("📧 BoxiBox - Campagne Email de Prospection")
    print("=" * 50)

    # Vérifier la configuration SMTP
    if not os.getenv('BREVO_SMTP_KEY'):
        print("\n⚠️  Variables BREVO non configurées!")
        setup_brevo_instructions()
        print("\nUtilisez --dry-run pour tester sans envoyer.")

    campaign = EmailCampaign(SMTP_CONFIG)

    # Charger les leads
    leads = campaign.load_leads(args.leads)
    print(f"\n📊 {len(leads)} leads chargés")

    # Limiter le nombre d'envois
    leads = leads[:args.limit]

    # Filtrer par score
    qualified_leads = [l for l in leads if int(l.get('score', 0)) >= 50]
    print(f"📊 {len(qualified_leads)} leads qualifiés (score >= 50)")

    # Lancer la campagne
    campaign.run_campaign(qualified_leads, args.template, dry_run=args.dry_run)


if __name__ == '__main__':
    main()
