#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
BoxiBox - Scraper de Prospection Self-Stockage
Scrape les Pages Jaunes et autres sources gratuites pour trouver des prospects.

Usage:
    python scraper_self_stockage.py --villes Paris,Lyon,Marseille --output leads.csv

Cout: 0 EUR
"""

import sys
import io

# Fix Windows console encoding
if sys.platform == 'win32':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')

import requests
from bs4 import BeautifulSoup
import csv
import json
import time
import re
import argparse
from datetime import datetime
from urllib.parse import quote_plus
import random

# User agents pour éviter le blocage
USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
]

class SelfStorageScraper:
    def __init__(self):
        self.session = requests.Session()
        self.leads = []

    def get_headers(self):
        return {
            'User-Agent': random.choice(USER_AGENTS),
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'fr-FR,fr;q=0.9,en;q=0.8',
            'Connection': 'keep-alive',
        }

    def scrape_pagesjaunes(self, ville: str) -> list:
        """Scrape Pages Jaunes pour une ville donnée"""
        leads = []
        base_url = f"https://www.pagesjaunes.fr/annuaire/chercherlespros?quoiqui=self+stockage&ou={quote_plus(ville)}"

        print(f"  📍 Scraping Pages Jaunes: {ville}...")

        try:
            response = self.session.get(base_url, headers=self.get_headers(), timeout=15)
            response.raise_for_status()

            soup = BeautifulSoup(response.text, 'html.parser')

            # Trouver tous les résultats
            listings = soup.select('.bi-bloc, .pj-list-item, [data-pjblock]')

            for listing in listings:
                try:
                    lead = self._parse_pagesjaunes_listing(listing, ville)
                    if lead and lead.get('name'):
                        leads.append(lead)
                except Exception as e:
                    continue

            print(f"    ✓ {len(leads)} résultats trouvés")

        except Exception as e:
            print(f"    ✗ Erreur: {str(e)[:50]}")

        return leads

    def _parse_pagesjaunes_listing(self, listing, ville: str) -> dict:
        """Parse un listing Pages Jaunes"""
        lead = {
            'source': 'pagesjaunes',
            'ville_recherche': ville,
            'scraped_at': datetime.now().isoformat(),
        }

        # Nom de l'entreprise
        name_el = listing.select_one('.bi-denomination, .denomination-link, h3 a, .company-name')
        if name_el:
            lead['name'] = name_el.get_text(strip=True)

        # Adresse
        addr_el = listing.select_one('.bi-address, .address, .adresse')
        if addr_el:
            lead['address'] = addr_el.get_text(strip=True)

        # Téléphone
        phone_el = listing.select_one('.bi-phone, .phone, .tel, [href^="tel:"]')
        if phone_el:
            phone = phone_el.get('href', '') or phone_el.get_text(strip=True)
            phone = re.sub(r'[^\d+]', '', phone.replace('tel:', ''))
            lead['phone'] = phone

        # Site web
        website_el = listing.select_one('a[href*="http"]:not([href*="pagesjaunes"])')
        if website_el:
            lead['website'] = website_el.get('href')

        return lead

    def scrape_google_maps_export(self, ville: str) -> list:
        """
        Alternative: Utiliser l'API gratuite Nominatim (OpenStreetMap)
        Moins de données mais 100% gratuit et légal
        """
        leads = []

        print(f"  [OSM] Recherche OpenStreetMap: {ville}...")

        # Recherche via Nominatim (gratuit, 1 req/sec)
        search_terms = [
            f'self stockage {ville} France',
            f'garde meuble {ville} France',
            f'box stockage {ville} France',
            f'shurgard {ville}',
            f'une piece en plus {ville}',
            f'homebox {ville}',
            f'annexx {ville}',
            f'a ta box {ville}',
            f'jestocke {ville}',
            f'lok\'nstore {ville}',
        ]

        for term in search_terms:
            url = f"https://nominatim.openstreetmap.org/search"
            params = {
                'q': term,
                'format': 'json',
                'addressdetails': 1,
                'limit': 30,
                'countrycodes': 'fr',
            }

            try:
                time.sleep(1.2)  # Rate limit Nominatim
                response = self.session.get(url, params=params, headers={
                    'User-Agent': 'BoxiBox-Prospection/1.0 (contact@boxibox.fr)'
                }, timeout=10)

                data = response.json()

                for item in data:
                    display = item.get('display_name', '').lower()
                    item_type = item.get('type', '').lower()
                    item_class = item.get('class', '').lower()

                    # Filtrer les résultats pertinents
                    keywords = ['stockage', 'storage', 'garde', 'box', 'entrepos', 'shurgard', 'homebox', 'annexx', 'jestocke']
                    if any(kw in display or kw in item_type for kw in keywords):
                        leads.append({
                            'name': item.get('display_name', '').split(',')[0].strip(),
                            'address': item.get('display_name'),
                            'city': item.get('address', {}).get('city') or item.get('address', {}).get('town') or ville,
                            'postal_code': item.get('address', {}).get('postcode', ''),
                            'lat': item.get('lat'),
                            'lon': item.get('lon'),
                            'source': 'openstreetmap',
                            'ville_recherche': ville,
                            'scraped_at': datetime.now().isoformat(),
                        })

            except Exception as e:
                print(f"    [!] Erreur: {str(e)[:40]}")

        print(f"    [OK] {len(leads)} resultats OSM")
        return leads

    def scrape_annuaire_entreprises(self, ville: str) -> list:
        """
        Scrape l'annuaire-entreprises.data.gouv.fr (API gratuite officielle)
        Code NAF 5210B = Entreposage et stockage non frigorifique
        """
        leads = []
        print(f"  [GOUV] Recherche annuaire-entreprises.data.gouv.fr: {ville}...")

        try:
            # Recherche par activite et localisation
            url = "https://recherche-entreprises.api.gouv.fr/search"
            params = {
                'q': f'stockage {ville}',
                'activite_principale': '52.10B',  # Code NAF entreposage
                'page': 1,
                'per_page': 25,
            }

            response = self.session.get(url, params=params, timeout=15)
            data = response.json()

            for result in data.get('results', []):
                siege = result.get('siege', {})
                name = result.get('nom_complet', result.get('nom_raison_sociale', ''))

                if name:
                    leads.append({
                        'name': name,
                        'address': f"{siege.get('numero_voie', '')} {siege.get('type_voie', '')} {siege.get('libelle_voie', '')}, {siege.get('code_postal', '')} {siege.get('libelle_commune', '')}".strip(),
                        'city': siege.get('libelle_commune', ville),
                        'postal_code': siege.get('code_postal', ''),
                        'siren': result.get('siren', ''),
                        'source': 'annuaire_entreprises',
                        'ville_recherche': ville,
                        'scraped_at': datetime.now().isoformat(),
                    })

            print(f"    [OK] {len(leads)} entreprises trouvees (API Gouv)")

        except Exception as e:
            print(f"    [!] Erreur API Gouv: {str(e)[:40]}")

        return leads

    def scrape_overpass_api(self, ville: str) -> list:
        """
        Utiliser Overpass API pour trouver les centres de stockage
        Plus precis que Nominatim pour les POI specifiques
        """
        leads = []
        print(f"  [OVERPASS] Recherche detaillee: {ville}...")

        # D'abord, obtenir les coordonnees de la ville
        try:
            time.sleep(1)
            geo_url = "https://nominatim.openstreetmap.org/search"
            geo_response = self.session.get(geo_url, params={
                'q': f'{ville}, France',
                'format': 'json',
                'limit': 1
            }, headers={'User-Agent': 'BoxiBox-Prospection/1.0'}, timeout=10)

            geo_data = geo_response.json()
            if not geo_data:
                print(f"    [!] Coordonnees non trouvees pour {ville}")
                return leads

            lat = float(geo_data[0]['lat'])
            lon = float(geo_data[0]['lon'])
            print(f"    Coordonnees: {lat}, {lon}")

            # Requete Overpass pour trouver les self-stockages dans un rayon de 30km
            overpass_url = "https://overpass-api.de/api/interpreter"

            # Query Overpass simplifiee et robuste
            query = f"""[out:json][timeout:30];
(
  node["amenity"="storage_rental"](around:30000,{lat},{lon});
  way["amenity"="storage_rental"](around:30000,{lat},{lon});
  node["shop"="storage_rental"](around:30000,{lat},{lon});
  way["shop"="storage_rental"](around:30000,{lat},{lon});
  node["landuse"="commercial"]["name"~"stockage|storage|garde-meuble|box",i](around:30000,{lat},{lon});
  way["landuse"="commercial"]["name"~"stockage|storage|garde-meuble|box",i](around:30000,{lat},{lon});
  node["building"]["name"~"stockage|storage|garde-meuble|box",i](around:30000,{lat},{lon});
  way["building"]["name"~"stockage|storage|garde-meuble|box",i](around:30000,{lat},{lon});
);
out body center;"""

            time.sleep(2)  # Respecter le rate limit
            response = self.session.post(
                overpass_url,
                data={'data': query},
                headers={'Content-Type': 'application/x-www-form-urlencoded'},
                timeout=45
            )

            if response.status_code != 200:
                print(f"    [!] Overpass HTTP {response.status_code}")
                return leads

            data = response.json()

            for element in data.get('elements', []):
                tags = element.get('tags', {})
                name = tags.get('name', '')

                if name:
                    # Coordonnees: soit directement, soit depuis 'center' pour les ways
                    elem_lat = element.get('lat') or element.get('center', {}).get('lat')
                    elem_lon = element.get('lon') or element.get('center', {}).get('lon')

                    leads.append({
                        'name': name,
                        'address': f"{tags.get('addr:housenumber', '')} {tags.get('addr:street', '')}, {tags.get('addr:postcode', '')} {tags.get('addr:city', ville)}".strip().strip(',').strip(),
                        'city': tags.get('addr:city', ville),
                        'postal_code': tags.get('addr:postcode', ''),
                        'phone': tags.get('phone', tags.get('contact:phone', '')),
                        'website': tags.get('website', tags.get('contact:website', '')),
                        'email': tags.get('email', tags.get('contact:email', '')),
                        'lat': elem_lat,
                        'lon': elem_lon,
                        'source': 'overpass',
                        'ville_recherche': ville,
                        'scraped_at': datetime.now().isoformat(),
                    })

            print(f"    [OK] {len(leads)} resultats Overpass")

        except requests.exceptions.Timeout:
            print(f"    [!] Timeout Overpass (serveur charge)")
        except json.JSONDecodeError as e:
            print(f"    [!] Reponse invalide Overpass")
        except Exception as e:
            print(f"    [!] Erreur Overpass: {type(e).__name__}: {str(e)[:40]}")

        return leads

    def scrape_societe_com(self, ville: str) -> list:
        """
        Scrape Societe.com pour le code NAF 5210B (entreposage)
        """
        leads = []

        print(f"  📍 Recherche Societe.com: {ville}...")

        # Note: Societe.com a une protection anti-bot forte
        # Cette méthode peut nécessiter des ajustements
        url = f"https://www.societe.com/cgi-bin/search?champs={quote_plus(f'self stockage {ville}')}"

        try:
            response = self.session.get(url, headers=self.get_headers(), timeout=15)
            soup = BeautifulSoup(response.text, 'html.parser')

            results = soup.select('.ResultSociete, .company-item')

            for result in results[:20]:
                name_el = result.select_one('a.txt-no-style, .company-name')
                if name_el:
                    leads.append({
                        'name': name_el.get_text(strip=True),
                        'source': 'societe.com',
                        'ville_recherche': ville,
                    })

            print(f"    ✓ {len(leads)} entreprises trouvées")

        except Exception as e:
            print(f"    ✗ Erreur Societe.com: {str(e)[:50]}")

        return leads

    def guess_email(self, lead: dict) -> str:
        """
        Devine l'email à partir du site web (patterns communs)
        Gratuit et souvent efficace!
        """
        website = lead.get('website', '')
        if not website:
            return None

        # Extraire le domaine
        domain = re.sub(r'https?://(www\.)?', '', website).split('/')[0]

        # Patterns d'email communs en France
        patterns = [
            f'contact@{domain}',
            f'info@{domain}',
            f'accueil@{domain}',
            f'reservation@{domain}',
            f'commercial@{domain}',
        ]

        # Vérifier si l'email existe (MX record check)
        # Pour simplifier, on retourne le pattern le plus commun
        return patterns[0]

    def verify_email_exists(self, email: str) -> bool:
        """
        Vérifie basiquement si le domaine a des MX records
        """
        import socket
        try:
            domain = email.split('@')[1]
            socket.getaddrinfo(domain, 25)
            return True
        except:
            return False

    def calculate_score(self, lead: dict) -> int:
        """Calcule un score de qualité du lead"""
        score = 40  # Base

        if lead.get('phone'):
            score += 20
        if lead.get('website'):
            score += 20
        if lead.get('email'):
            score += 15
        if lead.get('address'):
            score += 5

        return min(score, 100)

    def scrape_all(self, villes: list) -> list:
        """Scrape toutes les sources pour toutes les villes"""
        all_leads = []

        for ville in villes:
            print(f"\n>>> Ville: {ville}")
            print("-" * 40)

            # 1. API Gouvernementale (la plus fiable, 100% gratuite)
            gouv_leads = self.scrape_annuaire_entreprises(ville)
            all_leads.extend(gouv_leads)
            time.sleep(random.uniform(1, 2))

            # 2. OpenStreetMap Nominatim (recherche de marques connues)
            osm_leads = self.scrape_google_maps_export(ville)
            all_leads.extend(osm_leads)
            time.sleep(random.uniform(1, 2))

            # 3. Overpass API (si disponible)
            overpass_leads = self.scrape_overpass_api(ville)
            all_leads.extend(overpass_leads)

            # Pause entre villes
            time.sleep(random.uniform(2, 4))

        # Dédupliquer par nom
        seen = set()
        unique_leads = []
        for lead in all_leads:
            name_key = lead.get('name', '').lower().strip()
            if name_key and name_key not in seen:
                seen.add(name_key)

                # Deviner l'email
                if lead.get('website') and not lead.get('email'):
                    lead['email'] = self.guess_email(lead)

                # Calculer le score
                lead['score'] = self.calculate_score(lead)
                lead['priority'] = 'hot' if lead['score'] >= 70 else 'warm' if lead['score'] >= 50 else 'cold'

                unique_leads.append(lead)

        return unique_leads

    def export_csv(self, leads: list, filename: str):
        """Exporte les leads en CSV"""
        if not leads:
            print("Aucun lead à exporter")
            return

        fieldnames = ['name', 'email', 'phone', 'website', 'address', 'score', 'priority', 'source', 'ville_recherche', 'scraped_at']

        with open(filename, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction='ignore')
            writer.writeheader()
            writer.writerows(leads)

        print(f"\n✅ {len(leads)} leads exportés vers {filename}")

    def export_json(self, leads: list, filename: str):
        """Exporte les leads en JSON (pour import API)"""
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(leads, f, ensure_ascii=False, indent=2)

        print(f"✅ {len(leads)} leads exportés vers {filename}")

    def send_to_boxibox_api(self, leads: list, api_url: str, api_key: str):
        """Envoie les leads à l'API BoxiBox"""
        print(f"\n📤 Envoi de {len(leads)} leads à BoxiBox...")

        success = 0
        errors = 0

        for lead in leads:
            if lead['score'] < 50:  # Skip low-quality leads
                continue

            try:
                response = requests.post(
                    f"{api_url}/api/v1/external/leads",
                    headers={
                        'X-API-Key': api_key,
                        'Content-Type': 'application/json',
                    },
                    json={
                        'first_name': '',
                        'last_name': '',
                        'email': lead.get('email', ''),
                        'phone': lead.get('phone', ''),
                        'company': lead.get('name', ''),
                        'source': f"scraper_{lead.get('source', 'manual')}",
                        'score': lead.get('score', 50),
                        'priority': lead.get('priority', 'warm'),
                        'notes': f"Adresse: {lead.get('address', 'N/A')}\nSite: {lead.get('website', 'N/A')}",
                        'metadata': {
                            'scraped_at': lead.get('scraped_at'),
                            'ville_recherche': lead.get('ville_recherche'),
                        }
                    },
                    timeout=10
                )

                if response.status_code == 201:
                    success += 1
                else:
                    errors += 1

                time.sleep(0.5)  # Rate limiting

            except Exception as e:
                errors += 1

        print(f"✅ {success} leads créés, {errors} erreurs")


def main():
    parser = argparse.ArgumentParser(description='Scraper de prospection self-stockage')
    parser.add_argument('--villes', type=str, default='Paris,Lyon,Marseille,Toulouse,Bordeaux',
                        help='Villes à scraper (séparées par des virgules)')
    parser.add_argument('--output', type=str, default='leads_self_stockage.csv',
                        help='Fichier de sortie')
    parser.add_argument('--format', type=str, choices=['csv', 'json', 'both'], default='csv',
                        help='Format de sortie')
    parser.add_argument('--api-url', type=str, help='URL de l\'API BoxiBox')
    parser.add_argument('--api-key', type=str, help='Clé API BoxiBox')

    args = parser.parse_args()

    villes = [v.strip() for v in args.villes.split(',')]

    print("=" * 50)
    print("🎯 BoxiBox - Scraper de Prospection Self-Stockage")
    print("=" * 50)
    print(f"Villes: {', '.join(villes)}")
    print(f"Output: {args.output}")

    scraper = SelfStorageScraper()
    leads = scraper.scrape_all(villes)

    print(f"\n📊 Résumé:")
    print(f"   Total leads uniques: {len(leads)}")
    print(f"   Leads HOT (score >= 70): {len([l for l in leads if l['score'] >= 70])}")
    print(f"   Leads WARM (score 50-69): {len([l for l in leads if 50 <= l['score'] < 70])}")
    print(f"   Leads COLD (score < 50): {len([l for l in leads if l['score'] < 50])}")

    # Export
    if args.format in ['csv', 'both']:
        scraper.export_csv(leads, args.output)
    if args.format in ['json', 'both']:
        json_file = args.output.replace('.csv', '.json')
        scraper.export_json(leads, json_file)

    # Envoi API si configuré
    if args.api_url and args.api_key:
        scraper.send_to_boxibox_api(leads, args.api_url, args.api_key)


if __name__ == '__main__':
    main()
