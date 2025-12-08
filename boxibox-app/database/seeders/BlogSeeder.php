<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Get an admin as author
        $author = User::whereHas('roles', fn($q) => $q->where('name', 'super_admin'))
            ->first() ?? User::first();

        // Create Categories
        $categories = [
            [
                'name' => 'Conseils Self-Stockage',
                'slug' => 'conseils-self-stockage',
                'description' => 'Astuces et bonnes pratiques pour optimiser votre stockage',
                'meta_title' => 'Conseils Self-Stockage | Blog Boxibox',
                'meta_description' => 'Découvrez nos meilleurs conseils pour bien utiliser votre espace de self-stockage.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Actualités du Secteur',
                'slug' => 'actualites-secteur',
                'description' => 'Les dernières nouvelles du marché du self-stockage',
                'meta_title' => 'Actualités Self-Stockage | Blog Boxibox',
                'meta_description' => 'Restez informé des dernières tendances et actualités du self-stockage.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Guides Pratiques',
                'slug' => 'guides-pratiques',
                'description' => 'Tutoriels et guides pour les gestionnaires de self-stockage',
                'meta_title' => 'Guides Self-Stockage | Blog Boxibox',
                'meta_description' => 'Guides complets pour gérer efficacement votre activité de self-stockage.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Témoignages Clients',
                'slug' => 'temoignages-clients',
                'description' => 'Découvrez les retours d\'expérience de nos clients',
                'meta_title' => 'Témoignages Clients | Blog Boxibox',
                'meta_description' => 'Lisez les témoignages de nos clients satisfaits.',
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            BlogCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // Create Tags
        $tags = [
            'organisation', 'déménagement', 'stockage', 'sécurité', 'économies',
            'entreprise', 'particuliers', 'conseils', 'tendances', 'innovation',
            'automatisation', 'gestion', 'occupation', 'revenus', 'marketing'
        ];

        foreach ($tags as $tagName) {
            BlogTag::firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['name' => ucfirst($tagName), 'slug' => Str::slug($tagName)]
            );
        }

        // Create Sample Blog Posts
        $posts = [
            [
                'title' => '10 astuces pour maximiser l\'occupation de vos boxes',
                'slug' => '10-astuces-maximiser-occupation-boxes',
                'excerpt' => 'Découvrez les stratégies éprouvées pour optimiser le taux d\'occupation de votre centre de self-stockage et augmenter vos revenus.',
                'content' => $this->getArticleContent1(),
                'category_slug' => 'conseils-self-stockage',
                'tags' => ['gestion', 'occupation', 'conseils'],
                'meta_title' => '10 Astuces pour Maximiser l\'Occupation - Boxibox',
                'meta_description' => 'Découvrez 10 stratégies éprouvées pour optimiser le taux d\'occupation de votre self-stockage.',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'title' => 'Comment choisir le bon logiciel de gestion de self-stockage ?',
                'slug' => 'choisir-logiciel-gestion-self-stockage',
                'excerpt' => 'Guide complet pour sélectionner le logiciel de gestion adapté à votre activité de self-stockage.',
                'content' => $this->getArticleContent2(),
                'category_slug' => 'guides-pratiques',
                'tags' => ['gestion', 'automatisation', 'innovation'],
                'meta_title' => 'Guide: Choisir son Logiciel Self-Stockage - Boxibox',
                'meta_description' => 'Guide complet pour bien choisir votre logiciel de gestion de self-stockage.',
                'status' => 'published',
                'is_featured' => true,
            ],
            [
                'title' => 'Les tendances du self-stockage en 2025',
                'slug' => 'tendances-self-stockage-2025',
                'excerpt' => 'Analyse des principales tendances qui façonneront l\'industrie du self-stockage cette année.',
                'content' => $this->getArticleContent3(),
                'category_slug' => 'actualites-secteur',
                'tags' => ['tendances', 'innovation', 'entreprise'],
                'meta_title' => 'Tendances Self-Stockage 2025 - Boxibox',
                'meta_description' => 'Découvrez les principales tendances du marché du self-stockage en 2025.',
                'status' => 'published',
                'is_featured' => false,
            ],
            [
                'title' => 'Guide du déménagement : comment bien utiliser son box de stockage',
                'slug' => 'guide-demenagement-box-stockage',
                'excerpt' => 'Conseils pratiques pour utiliser efficacement votre box de stockage lors d\'un déménagement.',
                'content' => $this->getArticleContent4(),
                'category_slug' => 'conseils-self-stockage',
                'tags' => ['déménagement', 'organisation', 'particuliers'],
                'meta_title' => 'Guide Déménagement & Stockage - Boxibox',
                'meta_description' => 'Tous nos conseils pour réussir votre déménagement avec un box de stockage.',
                'status' => 'published',
                'is_featured' => false,
            ],
        ];

        foreach ($posts as $postData) {
            $category = BlogCategory::where('slug', $postData['category_slug'])->first();
            $tagIds = BlogTag::whereIn('slug', array_map(fn($t) => Str::slug($t), $postData['tags']))->pluck('id');

            unset($postData['category_slug'], $postData['tags']);

            $post = BlogPost::firstOrCreate(
                ['slug' => $postData['slug']],
                array_merge($postData, [
                    'author_id' => $author?->id,
                    'category_id' => $category?->id,
                    'published_at' => now()->subDays(rand(1, 30)),
                    'locale' => 'fr',
                    'allow_comments' => true,
                ])
            );

            if ($tagIds->isNotEmpty()) {
                $post->tags()->syncWithoutDetaching($tagIds);
            }
        }
    }

    private function getArticleContent1(): string
    {
        return <<<HTML
<p class="lead">Le taux d'occupation est l'indicateur clé de performance pour tout gestionnaire de self-stockage. Voici nos 10 meilleures stratégies pour l'optimiser.</p>

<h2>1. Optimisez votre présence en ligne</h2>
<p>Aujourd'hui, plus de 80% des clients recherchent leur box de stockage en ligne. Assurez-vous d'avoir un site web responsive, un bon référencement local et des avis Google positifs.</p>

<h2>2. Proposez une tarification dynamique</h2>
<p>Adaptez vos prix en fonction de la demande, de la saisonnalité et du taux d'occupation de chaque taille de box. Les logiciels modernes comme Boxibox automatisent cette optimisation.</p>

<h2>3. Diversifiez vos offres</h2>
<p>Proposez différentes tailles de boxes, des options de stockage climatisé, et des services complémentaires comme l'assurance ou la vente de matériel d'emballage.</p>

<h2>4. Facilitez la réservation en ligne</h2>
<p>Un processus de réservation simple et rapide augmente significativement le taux de conversion. Permettez aux clients de réserver et payer 24/7.</p>

<h2>5. Investissez dans la sécurité</h2>
<p>Les caméras de surveillance, les serrures connectées et les systèmes d'alarme rassurent les clients et justifient des tarifs premium.</p>

<h2>6. Soignez l'expérience client</h2>
<p>Un accueil chaleureux, des locaux propres et un service client réactif fidélisent vos locataires et génèrent du bouche-à-oreille positif.</p>

<h2>7. Développez des partenariats locaux</h2>
<p>Collaborez avec des déménageurs, agents immobiliers et entreprises locales pour obtenir des recommandations.</p>

<h2>8. Utilisez l'email marketing</h2>
<p>Gardez le contact avec vos anciens clients et relancez les prospects qui n'ont pas finalisé leur réservation.</p>

<h2>9. Analysez vos données</h2>
<p>Suivez vos KPIs, identifiez les tendances et ajustez votre stratégie en conséquence grâce aux tableaux de bord analytiques.</p>

<h2>10. Automatisez vos opérations</h2>
<p>L'automatisation réduit les coûts, améliore l'efficacité et permet à votre équipe de se concentrer sur la relation client.</p>

<blockquote>
<p>"Depuis que nous utilisons Boxibox, notre taux d'occupation est passé de 75% à 92% en moins de 6 mois."</p>
<cite>— Jean-Pierre, gestionnaire à Lyon</cite>
</blockquote>

<h2>Conclusion</h2>
<p>En appliquant ces stratégies de manière cohérente, vous pouvez significativement améliorer votre taux d'occupation et la rentabilité de votre activité. Commencez par identifier les domaines où vous avez le plus de marge de progression.</p>
HTML;
    }

    private function getArticleContent2(): string
    {
        return <<<HTML
<p class="lead">Le choix d'un logiciel de gestion est une décision cruciale pour votre activité de self-stockage. Ce guide vous aidera à faire le bon choix.</p>

<h2>Pourquoi un logiciel de gestion est-il essentiel ?</h2>
<p>Un bon logiciel centralise toutes vos opérations : gestion des contrats, facturation, suivi des paiements, communication client, et reporting. Il vous fait gagner du temps et réduit les erreurs.</p>

<h2>Les fonctionnalités essentielles</h2>
<ul>
<li><strong>Gestion des contrats</strong> : création, renouvellement et résiliation simplifiés</li>
<li><strong>Facturation automatique</strong> : génération et envoi automatique des factures</li>
<li><strong>Portail client</strong> : permettre aux locataires de gérer leur compte en ligne</li>
<li><strong>Tableau de bord analytique</strong> : suivre vos KPIs en temps réel</li>
<li><strong>Intégrations</strong> : comptabilité, paiement en ligne, contrôle d'accès</li>
</ul>

<h2>SaaS vs On-Premise</h2>
<p>Les solutions SaaS (cloud) offrent plusieurs avantages : pas d'installation, mises à jour automatiques, accessibilité partout, et coût prévisible. Privilégiez cette approche pour plus de flexibilité.</p>

<h2>Questions à poser aux éditeurs</h2>
<ol>
<li>Quelle est la disponibilité du support technique ?</li>
<li>Comment sont gérées les sauvegardes de données ?</li>
<li>Y a-t-il des frais cachés (formation, import de données) ?</li>
<li>Puis-je voir des témoignages de clients similaires ?</li>
<li>Comment se passe la migration depuis mon système actuel ?</li>
</ol>

<h2>Conclusion</h2>
<p>Prenez le temps de tester plusieurs solutions avant de vous décider. Un bon logiciel doit s'adapter à vos processus, pas l'inverse.</p>
HTML;
    }

    private function getArticleContent3(): string
    {
        return <<<HTML
<p class="lead">L'industrie du self-stockage évolue rapidement. Découvrez les tendances qui marqueront l'année 2025.</p>

<h2>1. L'automatisation complète</h2>
<p>Les centres de stockage "sans personnel" se multiplient, grâce aux technologies de réservation en ligne, serrures connectées et kiosques automatiques.</p>

<h2>2. L'expérience client digitale</h2>
<p>Les applications mobiles, les chatbots et les signatures électroniques deviennent la norme pour offrir une expérience fluide aux locataires.</p>

<h2>3. Le stockage éco-responsable</h2>
<p>Les clients sont de plus en plus sensibles à l'impact environnemental. Panneaux solaires, éclairage LED et matériaux recyclables deviennent des arguments commerciaux.</p>

<h2>4. La tarification dynamique</h2>
<p>Comme dans l'hôtellerie, les prix s'adaptent en temps réel à la demande, optimisant les revenus tout au long de l'année.</p>

<h2>5. Les services à valeur ajoutée</h2>
<p>Au-delà du simple stockage, les centres proposent désormais le transport, l'emballage, la réception de colis et même des espaces de coworking.</p>

<h2>Conclusion</h2>
<p>Les acteurs qui adopteront ces tendances rapidement auront un avantage compétitif significatif dans les années à venir.</p>
HTML;
    }

    private function getArticleContent4(): string
    {
        return <<<HTML
<p class="lead">Un déménagement peut être stressant. Voici comment un box de stockage peut vous simplifier la vie.</p>

<h2>Avant le déménagement</h2>
<p>Réservez votre box plusieurs semaines à l'avance, surtout en période de forte demande (été, fin de mois). Estimez vos besoins en volume avec notre calculateur en ligne.</p>

<h2>Comment organiser votre box</h2>
<ul>
<li>Placez les objets lourds en bas et les plus légers en haut</li>
<li>Créez une allée centrale pour accéder facilement à tout</li>
<li>Étiquetez clairement tous vos cartons</li>
<li>Gardez les objets dont vous aurez besoin rapidement à l'avant</li>
</ul>

<h2>Ce qu'il ne faut pas stocker</h2>
<p>Évitez les denrées périssables, les produits inflammables, les objets de valeur non assurés et les documents importants.</p>

<h2>Optimiser les coûts</h2>
<p>Ne louez que la taille nécessaire, profitez des offres promotionnelles et envisagez une durée de location flexible si votre situation peut évoluer.</p>

<h2>Conclusion</h2>
<p>Un box de stockage bien utilisé vous offre la flexibilité nécessaire pour un déménagement serein, que ce soit pour une transition temporaire ou un stockage à long terme.</p>
HTML;
    }
}
