#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
BoxiBox - Enrichisseur de Leads (GRATUIT)
Enrichit les leads avec site web et email en utilisant des sources gratuites.

Usage:
    python enrichir_leads.py --input leads.csv --output leads_enrichis.csv

Cout: 0 EUR
"""

import sys
import io

# Fix Windows console encoding
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

import csv
import re
import time
import argparse
import requests
from urllib.parse import quote_plus
import json

class LeadEnricher:
    def __init__(self):
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'BoxiBox-Enrichment/1.0 (contact@boxibox.fr)'
        })

        # Base de donnees de sites web connus des operateurs de self-stockage
        self.known_operators = {
            'shurgard': {'website': 'https://www.shurgard.fr', 'email': 'info.fr@shurgard.com'},
            'une piece en plus': {'website': 'https://www.unepieceenplus.com', 'email': 'contact@unepieceenplus.com'},
            'homebox': {'website': 'https://www.homebox.fr', 'email': 'contact@homebox.fr'},
            'annexx': {'website': 'https://www.annexx.com', 'email': 'contact@annexx.com'},
            'jestocke': {'website': 'https://www.jestocke.com', 'email': 'contact@jestocke.com'},
            'a ta box': {'website': 'https://www.atabox.fr', 'email': 'contact@atabox.fr'},
            'bluebox': {'website': 'https://www.bluebox.fr', 'email': 'contact@bluebox.fr'},
            'locabox': {'website': 'https://www.locabox.fr', 'email': 'contact@locabox.fr'},
            'ouistock': {'website': 'https://www.ouistock.fr', 'email': 'contact@ouistock.fr'},
            'safestore': {'website': 'https://www.safestore.fr', 'email': 'info@safestore.fr'},
            'lok\'nstore': {'website': 'https://www.loknstore.fr', 'email': 'contact@loknstore.fr'},
            'access self storage': {'website': 'https://www.accessselfstorage.fr', 'email': 'contact@accessselfstorage.fr'},
            'resotainer': {'website': 'https://www.resotainer.fr', 'email': 'contact@resotainer.fr'},
            'box avenue': {'website': 'https://www.box-avenue.fr', 'email': 'contact@box-avenue.fr'},
            'easy box': {'website': 'https://www.easybox.fr', 'email': 'contact@easybox.fr'},
            'abcd box': {'website': 'https://www.abcdbox.fr', 'email': 'contact@abcdbox.fr'},
            'stockage box': {'website': 'https://www.stockagebox.fr', 'email': 'contact@stockagebox.fr'},
        }

    def load_leads(self, filename: str) -> list:
        """Charge les leads depuis un CSV"""
        leads = []
        with open(filename, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            for row in reader:
                leads.append(dict(row))
        return leads

    def match_known_operator(self, name: str) -> dict:
        """Verifie si le nom correspond a un operateur connu"""
        name_lower = name.lower()
        for key, data in self.known_operators.items():
            if key in name_lower:
                return data
        return None

    def search_website_via_duckduckgo(self, company_name: str, city: str) -> str:
        """Recherche le site web via DuckDuckGo (gratuit, pas d'API key)"""
        try:
            # DuckDuckGo Instant Answer API (gratuit)
            query = f"{company_name} {city} self stockage site officiel"
            url = f"https://api.duckduckgo.com/?q={quote_plus(query)}&format=json&no_redirect=1"

            response = self.session.get(url, timeout=10)
            data = response.json()

            # Extraire l'URL du resultat abstrait
            abstract_url = data.get('AbstractURL', '')
            if abstract_url and 'wikipedia' not in abstract_url.lower():
                return abstract_url

            # Verifier les resultats relies
            for result in data.get('Results', [])[:3]:
                result_url = result.get('FirstURL', '')
                if result_url and self._is_valid_company_url(result_url, company_name):
                    return result_url

        except Exception as e:
            pass

        return None

    def search_website_via_google_cse(self, company_name: str, city: str) -> str:
        """
        Recherche via Google Custom Search Engine gratuit (100 requetes/jour)
        Necessite une cle API (gratuite) - optionnel
        """
        api_key = ''  # Mettre votre cle Google CSE ici si vous en avez une
        cx = ''  # Votre ID de moteur de recherche

        if not api_key or not cx:
            return None

        try:
            query = f"{company_name} {city} self stockage"
            url = f"https://www.googleapis.com/customsearch/v1?key={api_key}&cx={cx}&q={quote_plus(query)}"

            response = self.session.get(url, timeout=10)
            data = response.json()

            for item in data.get('items', [])[:3]:
                link = item.get('link', '')
                if self._is_valid_company_url(link, company_name):
                    return link

        except Exception:
            pass

        return None

    def _is_valid_company_url(self, url: str, company_name: str) -> bool:
        """Verifie si l'URL semble appartenir a l'entreprise"""
        if not url:
            return False

        # Exclure les annuaires et reseaux sociaux
        exclude = ['pagesjaunes', 'societe.com', 'facebook', 'linkedin', 'twitter', 'instagram', 'wikipedia']
        for ex in exclude:
            if ex in url.lower():
                return False

        # Verifier si le nom de l'entreprise est dans le domaine
        company_clean = re.sub(r'[^a-z0-9]', '', company_name.lower())[:10]
        domain_clean = re.sub(r'[^a-z0-9]', '', url.lower())

        # Au moins 5 caracteres du nom dans le domaine
        if len(company_clean) >= 5 and company_clean[:5] in domain_clean:
            return True

        return True  # Accepter si pas d'exclusion

    def guess_email_from_website(self, website: str) -> str:
        """Devine l'email a partir du site web"""
        if not website:
            return None

        # Extraire le domaine
        domain_match = re.search(r'https?://(?:www\.)?([^/]+)', website)
        if not domain_match:
            return None

        domain = domain_match.group(1)

        # Patterns d'email courants
        return f"contact@{domain}"

    def enrich_via_pappers_free(self, siren: str) -> dict:
        """
        Utilise l'API Pappers gratuite pour enrichir les donnees
        Limite: 100 requetes/mois gratuit
        """
        if not siren or len(str(siren)) != 9:
            return {}

        try:
            url = f"https://api.pappers.fr/v2/entreprise?siren={siren}"
            response = self.session.get(url, timeout=10)

            if response.status_code == 200:
                data = response.json()
                return {
                    'website': data.get('site_web'),
                    'phone': data.get('telephone'),
                    'email': data.get('email'),
                }
        except Exception:
            pass

        return {}

    def calculate_score(self, lead: dict) -> int:
        """Recalcule le score avec les nouvelles donnees"""
        score = 40  # Base

        if lead.get('phone'):
            score += 20
        if lead.get('website'):
            score += 20
        if lead.get('email'):
            score += 15
        if lead.get('address'):
            score += 5
        if lead.get('siren'):
            score += 5

        return min(score, 100)

    def enrich_lead(self, lead: dict) -> dict:
        """Enrichit un lead avec des donnees supplementaires"""
        name = lead.get('name', '')
        city = lead.get('city', lead.get('ville_recherche', ''))

        # 1. Verifier si c'est un operateur connu
        known = self.match_known_operator(name)
        if known:
            if not lead.get('website'):
                lead['website'] = known.get('website')
            if not lead.get('email'):
                lead['email'] = known.get('email')
            lead['is_known_operator'] = True

        # 2. Rechercher le site web si pas trouve
        if not lead.get('website'):
            website = self.search_website_via_duckduckgo(name, city)
            if website:
                lead['website'] = website

        # 3. Deviner l'email depuis le site web
        if lead.get('website') and not lead.get('email'):
            lead['email'] = self.guess_email_from_website(lead['website'])

        # 4. Enrichir via SIREN si disponible
        if lead.get('siren') and (not lead.get('website') or not lead.get('phone')):
            pappers_data = self.enrich_via_pappers_free(lead['siren'])
            if pappers_data:
                if pappers_data.get('website') and not lead.get('website'):
                    lead['website'] = pappers_data['website']
                if pappers_data.get('phone') and not lead.get('phone'):
                    lead['phone'] = pappers_data['phone']
                if pappers_data.get('email') and not lead.get('email'):
                    lead['email'] = pappers_data['email']

        # 5. Recalculer le score
        lead['score'] = self.calculate_score(lead)
        lead['priority'] = 'hot' if lead['score'] >= 70 else 'warm' if lead['score'] >= 50 else 'cold'

        return lead

    def enrich_all(self, leads: list) -> list:
        """Enrichit tous les leads"""
        enriched = []

        for i, lead in enumerate(leads):
            print(f"[{i+1}/{len(leads)}] Enrichissement: {lead.get('name', 'N/A')[:40]}...")

            enriched_lead = self.enrich_lead(lead)
            enriched.append(enriched_lead)

            status = 'OK' if enriched_lead.get('website') or enriched_lead.get('email') else '---'
            score = enriched_lead.get('score', 0)
            priority = enriched_lead.get('priority', 'cold')

            print(f"    {status} Score: {score} ({priority})")

            # Rate limiting
            time.sleep(0.5)

        return enriched

    def export_csv(self, leads: list, filename: str):
        """Exporte les leads enrichis en CSV"""
        if not leads:
            print("Aucun lead a exporter")
            return

        fieldnames = ['name', 'email', 'phone', 'website', 'address', 'city', 'postal_code',
                      'score', 'priority', 'source', 'siren', 'ville_recherche', 'scraped_at']

        with open(filename, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
            writer.writeheader()
            writer.writerows(leads)

        print(f"\n[OK] {len(leads)} leads enrichis exportes vers {filename}")


def main():
    parser = argparse.ArgumentParser(description='Enrichisseur de leads')
    parser.add_argument('--input', type=str, required=True, help='Fichier CSV des leads')
    parser.add_argument('--output', type=str, default='leads_enrichis.csv', help='Fichier de sortie')

    args = parser.parse_args()

    print("=" * 50)
    print("BoxiBox - Enrichisseur de Leads (GRATUIT)")
    print("=" * 50)

    enricher = LeadEnricher()

    # Charger les leads
    leads = enricher.load_leads(args.input)
    print(f"\n{len(leads)} leads charges depuis {args.input}")

    # Enrichir
    enriched = enricher.enrich_all(leads)

    # Stats
    hot = len([l for l in enriched if l.get('priority') == 'hot'])
    warm = len([l for l in enriched if l.get('priority') == 'warm'])
    cold = len([l for l in enriched if l.get('priority') == 'cold'])
    with_email = len([l for l in enriched if l.get('email')])
    with_website = len([l for l in enriched if l.get('website')])

    print(f"\nResultat:")
    print(f"  HOT (score >= 70):  {hot}")
    print(f"  WARM (score 50-69): {warm}")
    print(f"  COLD (score < 50):  {cold}")
    print(f"  Avec email:         {with_email}")
    print(f"  Avec site web:      {with_website}")

    # Exporter
    enricher.export_csv(enriched, args.output)


if __name__ == '__main__':
    main()
