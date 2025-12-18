<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Site;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIChatbotService
{
    /**
     * Catégories de questions FAQ avec réponses automatiques
     */
    protected array $faqCategories = [
        'pricing' => [
            'keywords' => ['prix', 'tarif', 'coût', 'combien', 'price', 'cost', 'rate', 'mensuel', 'monthly'],
            'intent' => 'pricing_inquiry',
        ],
        'availability' => [
            'keywords' => ['disponible', 'libre', 'available', 'dispo', 'place', 'vacancy'],
            'intent' => 'availability_check',
        ],
        'size' => [
            'keywords' => ['taille', 'dimension', 'size', 'm2', 'm²', 'mètre', 'surface', 'grand', 'petit'],
            'intent' => 'size_recommendation',
        ],
        'access' => [
            'keywords' => ['accès', 'horaire', 'ouvert', 'access', 'hours', 'open', 'code', 'clé', 'key'],
            'intent' => 'access_info',
        ],
        'payment' => [
            'keywords' => ['paiement', 'payer', 'facture', 'payment', 'pay', 'invoice', 'carte', 'card', 'sepa'],
            'intent' => 'payment_info',
        ],
        'contract' => [
            'keywords' => ['contrat', 'résilier', 'durée', 'contract', 'cancel', 'terminate', 'duration', 'engagement'],
            'intent' => 'contract_info',
        ],
        'security' => [
            'keywords' => ['sécurité', 'caméra', 'alarme', 'security', 'camera', 'safe', 'surveillance', 'assurance'],
            'intent' => 'security_info',
        ],
        'moving' => [
            'keywords' => ['déménagement', 'moving', 'carton', 'box', 'emballage', 'transport'],
            'intent' => 'moving_help',
        ],
    ];

    /**
     * Réponses prédéfinies pour les FAQ
     */
    protected array $faqResponses = [
        'pricing_inquiry' => [
            'fr' => "Nos tarifs varient selon la taille du box :\n\n📦 **Petits boxes (1-3m²)** : à partir de {min_price}€/mois\n📦 **Moyens boxes (4-8m²)** : à partir de {mid_price}€/mois\n📦 **Grands boxes (9m²+)** : à partir de {max_price}€/mois\n\nVoulez-vous que je vous aide à trouver le box idéal pour vos besoins ?",
            'en' => "Our rates vary by unit size:\n\n📦 **Small units (1-3m²)**: from {min_price}€/month\n📦 **Medium units (4-8m²)**: from {mid_price}€/month\n📦 **Large units (9m²+)**: from {max_price}€/month\n\nWould you like help finding the perfect unit for your needs?",
        ],
        'availability_check' => [
            'fr' => "🟢 Bonne nouvelle ! Nous avons actuellement **{available_count} boxes disponibles** sur notre site.\n\nTailles disponibles :\n{available_sizes}\n\nSouhaitez-vous réserver un box ou visiter notre centre ?",
            'en' => "🟢 Great news! We currently have **{available_count} units available**.\n\nAvailable sizes:\n{available_sizes}\n\nWould you like to book a unit or visit our facility?",
        ],
        'size_recommendation' => [
            'fr' => "Je peux vous aider à choisir la bonne taille ! 📏\n\n**Guide rapide :**\n• 1-2m² : Cartons, petits meubles\n• 3-5m² : Studio/1 pièce\n• 6-10m² : Appartement 2-3 pièces\n• 12m²+ : Maison complète\n\nQu'avez-vous besoin de stocker ?",
            'en' => "I can help you choose the right size! 📏\n\n**Quick guide:**\n• 1-2m²: Boxes, small furniture\n• 3-5m²: Studio/1 room\n• 6-10m²: 2-3 bedroom apartment\n• 12m²+: Full house\n\nWhat do you need to store?",
        ],
        'access_info' => [
            'fr' => "🔐 **Accès à votre box :**\n\n• Accès 7j/7 de {open_time} à {close_time}\n• Code personnel sécurisé\n• Accès via app mobile ou digicode\n• Badges disponibles sur demande\n\nAvez-vous d'autres questions sur l'accès ?",
            'en' => "🔐 **Unit access:**\n\n• 7 days a week from {open_time} to {close_time}\n• Personal secure code\n• Access via mobile app or keypad\n• Badges available on request\n\nDo you have other questions about access?",
        ],
        'payment_info' => [
            'fr' => "💳 **Modes de paiement acceptés :**\n\n• Carte bancaire (Visa, Mastercard)\n• Prélèvement SEPA automatique\n• Virement bancaire\n\nLa facturation est mensuelle, prélevée le 1er du mois.\n\nSouhaitez-vous mettre en place un prélèvement automatique ?",
            'en' => "💳 **Accepted payment methods:**\n\n• Credit card (Visa, Mastercard)\n• SEPA direct debit\n• Bank transfer\n\nBilling is monthly, charged on the 1st.\n\nWould you like to set up automatic payment?",
        ],
        'contract_info' => [
            'fr' => "📝 **Informations contrat :**\n\n• **Sans engagement** - Résiliez quand vous voulez\n• Préavis de 15 jours\n• Signature électronique\n• Assurance incluse ou en option\n\nVoulez-vous consulter nos conditions générales ?",
            'en' => "📝 **Contract information:**\n\n• **No commitment** - Cancel anytime\n• 15 days notice\n• Electronic signature\n• Insurance included or optional\n\nWould you like to see our terms and conditions?",
        ],
        'security_info' => [
            'fr' => "🛡️ **Sécurité de vos biens :**\n\n• Vidéosurveillance 24h/24\n• Alarme intrusion\n• Accès individuel sécurisé\n• Assurance disponible jusqu'à {max_insurance}€\n• Site clôturé et éclairé\n\nVotre tranquillité est notre priorité !",
            'en' => "🛡️ **Security of your belongings:**\n\n• 24/7 video surveillance\n• Intrusion alarm\n• Secure individual access\n• Insurance available up to {max_insurance}€\n• Fenced and lit facility\n\nYour peace of mind is our priority!",
        ],
        'moving_help' => [
            'fr' => "📦 **Aide au déménagement :**\n\n• Cartons et matériel d'emballage en vente sur place\n• Chariots disponibles gratuitement\n• Partenaires déménageurs recommandés\n\nBesoin de plus d'informations ?",
            'en' => "📦 **Moving help:**\n\n• Boxes and packing materials for sale on-site\n• Free trolleys available\n• Recommended moving partners\n\nNeed more information?",
        ],
    ];

    /**
     * Contexte du tenant pour personnalisation
     */
    protected ?int $tenantId = null;
    protected ?int $siteId = null;
    protected string $locale = 'fr';

    /**
     * Set the tenant context
     */
    public function setContext(?int $tenantId, ?int $siteId = null, string $locale = 'fr'): self
    {
        $this->tenantId = $tenantId;
        $this->siteId = $siteId;
        $this->locale = $locale;
        return $this;
    }

    /**
     * Process an incoming message and generate a response
     */
    public function processMessage(string $message, ?string $conversationId = null, ?int $customerId = null): array
    {
        $startTime = microtime(true);

        // Detect intent from message
        $intent = $this->detectIntent($message);

        // Check if this is a FAQ that can be auto-answered
        if ($intent && isset($this->faqResponses[$intent])) {
            $response = $this->generateFaqResponse($intent);
            $source = 'faq';
            $confidence = 0.9;
        } else {
            // Use AI for complex queries or fallback
            $response = $this->generateAIResponse($message, $conversationId, $customerId);
            $source = 'ai';
            $confidence = $response['confidence'] ?? 0.7;
            $response = $response['message'];
        }

        // Log the interaction
        $this->logInteraction($message, $response, $intent, $source, $confidence);

        $processingTime = round((microtime(true) - $startTime) * 1000);

        return [
            'message' => $response,
            'intent' => $intent,
            'source' => $source,
            'confidence' => $confidence,
            'processing_time_ms' => $processingTime,
            'suggested_actions' => $this->getSuggestedActions($intent),
        ];
    }

    /**
     * Detect the intent of a message
     */
    protected function detectIntent(string $message): ?string
    {
        $message = mb_strtolower($message);

        foreach ($this->faqCategories as $category => $config) {
            foreach ($config['keywords'] as $keyword) {
                if (str_contains($message, mb_strtolower($keyword))) {
                    return $config['intent'];
                }
            }
        }

        return null;
    }

    /**
     * Generate a FAQ response with dynamic data
     */
    protected function generateFaqResponse(string $intent): string
    {
        $template = $this->faqResponses[$intent][$this->locale]
            ?? $this->faqResponses[$intent]['fr'];

        // Get dynamic data based on context
        $replacements = $this->getDynamicReplacements($intent);

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );
    }

    /**
     * Get dynamic data for template replacements
     */
    protected function getDynamicReplacements(string $intent): array
    {
        $replacements = [];

        switch ($intent) {
            case 'pricing_inquiry':
                $prices = $this->getBoxPriceRanges();
                $replacements = [
                    '{min_price}' => $prices['min'] ?? '29',
                    '{mid_price}' => $prices['mid'] ?? '59',
                    '{max_price}' => $prices['max'] ?? '99',
                ];
                break;

            case 'availability_check':
                $availability = $this->getAvailability();
                $replacements = [
                    '{available_count}' => $availability['count'] ?? '0',
                    '{available_sizes}' => $availability['sizes'] ?? 'Contactez-nous pour plus d\'informations',
                ];
                break;

            case 'access_info':
                $site = $this->getSiteInfo();
                $replacements = [
                    '{open_time}' => $site['open_time'] ?? '6h00',
                    '{close_time}' => $site['close_time'] ?? '22h00',
                ];
                break;

            case 'security_info':
                $replacements = [
                    '{max_insurance}' => '50 000',
                ];
                break;
        }

        return $replacements;
    }

    /**
     * Get box price ranges for the tenant/site
     */
    protected function getBoxPriceRanges(): array
    {
        $cacheKey = "chatbot_prices_{$this->tenantId}_{$this->siteId}";

        return Cache::remember($cacheKey, 3600, function () {
            $query = Box::where('status', 'available');

            if ($this->tenantId) {
                $query->where('tenant_id', $this->tenantId);
            }
            if ($this->siteId) {
                $query->where('site_id', $this->siteId);
            }

            $boxes = $query->get();

            if ($boxes->isEmpty()) {
                return ['min' => '29', 'mid' => '59', 'max' => '99'];
            }

            $prices = $boxes->pluck('current_price')->filter()->sort()->values();
            $count = $prices->count();

            return [
                'min' => number_format($prices->first() ?? 29, 0),
                'mid' => number_format($prices->get((int)($count / 2)) ?? 59, 0),
                'max' => number_format($prices->last() ?? 99, 0),
            ];
        });
    }

    /**
     * Get availability information
     */
    protected function getAvailability(): array
    {
        $cacheKey = "chatbot_availability_{$this->tenantId}_{$this->siteId}";

        return Cache::remember($cacheKey, 300, function () {
            $query = Box::where('status', 'available');

            if ($this->tenantId) {
                $query->where('tenant_id', $this->tenantId);
            }
            if ($this->siteId) {
                $query->where('site_id', $this->siteId);
            }

            $boxes = $query->get();

            $sizeGroups = $boxes->groupBy(function ($box) {
                $size = $box->size_m2 ?? $box->volume ?? 0;
                if ($size <= 3) return 'small';
                if ($size <= 8) return 'medium';
                return 'large';
            });

            $sizesText = [];
            if ($sizeGroups->has('small')) {
                $sizesText[] = "• Petits (1-3m²): {$sizeGroups['small']->count()} disponibles";
            }
            if ($sizeGroups->has('medium')) {
                $sizesText[] = "• Moyens (4-8m²): {$sizeGroups['medium']->count()} disponibles";
            }
            if ($sizeGroups->has('large')) {
                $sizesText[] = "• Grands (9m²+): {$sizeGroups['large']->count()} disponibles";
            }

            return [
                'count' => $boxes->count(),
                'sizes' => implode("\n", $sizesText) ?: 'Contactez-nous pour plus d\'informations',
            ];
        });
    }

    /**
     * Get site information
     */
    protected function getSiteInfo(): array
    {
        if (!$this->siteId) {
            return ['open_time' => '6h00', 'close_time' => '22h00'];
        }

        $site = Cache::remember("site_info_{$this->siteId}", 3600, function () {
            return Site::find($this->siteId);
        });

        return [
            'open_time' => $site->access_hours_start ?? '6h00',
            'close_time' => $site->access_hours_end ?? '22h00',
        ];
    }

    /**
     * Generate AI response for complex queries
     */
    protected function generateAIResponse(string $message, ?string $conversationId, ?int $customerId): array
    {
        // Build context for AI
        $context = $this->buildAIContext($conversationId, $customerId);

        // Check if we should use external AI API
        $aiProvider = config('services.ai.provider', 'local');

        if ($aiProvider === 'openai' && config('services.openai.api_key')) {
            return $this->callOpenAI($message, $context);
        }

        // Fallback to local rule-based response
        return $this->generateLocalResponse($message, $context);
    }

    /**
     * Build context for AI response
     */
    protected function buildAIContext(?string $conversationId, ?int $customerId): array
    {
        $context = [
            'tenant_id' => $this->tenantId,
            'site_id' => $this->siteId,
            'locale' => $this->locale,
        ];

        // Add customer context if available
        if ($customerId) {
            $customer = Customer::with(['contracts.box'])->find($customerId);
            if ($customer) {
                $context['customer'] = [
                    'name' => $customer->full_name,
                    'has_active_contract' => $customer->contracts->where('status', 'active')->isNotEmpty(),
                    'box_numbers' => $customer->contracts
                        ->where('status', 'active')
                        ->pluck('box.name')
                        ->toArray(),
                ];
            }
        }

        // Add conversation history if available
        if ($conversationId) {
            $context['history'] = ChatMessage::where('conversation_id', $conversationId)
                ->latest()
                ->take(10)
                ->get()
                ->reverse()
                ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
                ->toArray();
        }

        return $context;
    }

    /**
     * Call OpenAI API for response
     */
    protected function callOpenAI(string $message, array $context): array
    {
        try {
            $systemPrompt = $this->buildSystemPrompt($context);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            // Add conversation history
            if (!empty($context['history'])) {
                foreach ($context['history'] as $msg) {
                    $messages[] = $msg;
                }
            }

            $messages[] = ['role' => 'user', 'content' => $message];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => $messages,
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'message' => $data['choices'][0]['message']['content'] ?? $this->getFallbackResponse(),
                    'confidence' => 0.85,
                ];
            }

            Log::warning('OpenAI API call failed', ['response' => $response->body()]);

        } catch (\Exception $e) {
            Log::error('OpenAI API error', ['error' => $e->getMessage()]);
        }

        return [
            'message' => $this->getFallbackResponse(),
            'confidence' => 0.5,
        ];
    }

    /**
     * Build system prompt for AI
     */
    protected function buildSystemPrompt(array $context): string
    {
        $lang = $this->locale === 'fr' ? 'français' : 'English';

        $prompt = "Tu es un assistant virtuel pour un centre de self-stockage. Tu dois répondre en {$lang}.

Ton rôle est d'aider les clients avec :
- Les informations sur les tarifs et disponibilités
- Le choix de la taille de box appropriée
- Les questions sur l'accès et la sécurité
- Les informations sur les contrats et paiements

Règles importantes :
1. Sois toujours poli et professionnel
2. Réponds de manière concise mais complète
3. Si tu ne connais pas la réponse exacte, propose de contacter un conseiller
4. N'invente jamais de prix ou de disponibilités
5. Encourage les clients à visiter le centre ou réserver en ligne";

        if (!empty($context['customer'])) {
            $prompt .= "\n\nContexte client : {$context['customer']['name']}";
            if ($context['customer']['has_active_contract']) {
                $prompt .= " (client actif avec box: " . implode(', ', $context['customer']['box_numbers']) . ")";
            }
        }

        return $prompt;
    }

    /**
     * Generate local rule-based response
     */
    protected function generateLocalResponse(string $message, array $context): array
    {
        // Simple keyword-based responses for common queries not in FAQ
        $message = mb_strtolower($message);

        if (str_contains($message, 'bonjour') || str_contains($message, 'hello') || str_contains($message, 'salut')) {
            $greeting = $this->locale === 'fr'
                ? "Bonjour ! 👋 Je suis votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?\n\nJe peux vous renseigner sur :\n• Nos tarifs et disponibilités\n• Le choix de la taille de box\n• L'accès et la sécurité\n• Les modalités de location"
                : "Hello! 👋 I'm your virtual assistant. How can I help you today?\n\nI can help you with:\n• Our rates and availability\n• Choosing the right unit size\n• Access and security\n• Rental terms";

            return ['message' => $greeting, 'confidence' => 0.95];
        }

        if (str_contains($message, 'merci') || str_contains($message, 'thank')) {
            $thanks = $this->locale === 'fr'
                ? "Je vous en prie ! 😊 N'hésitez pas si vous avez d'autres questions. Je reste à votre disposition."
                : "You're welcome! 😊 Don't hesitate if you have other questions. I'm here to help.";

            return ['message' => $thanks, 'confidence' => 0.95];
        }

        if (str_contains($message, 'réserver') || str_contains($message, 'book') || str_contains($message, 'louer')) {
            $booking = $this->locale === 'fr'
                ? "Super ! 🎉 Pour réserver un box, vous pouvez :\n\n1. **Réserver en ligne** - Rapide et sécurisé\n2. **Nous appeler** - Un conseiller vous accompagne\n3. **Visiter le centre** - Voir les boxes sur place\n\nQuelle option préférez-vous ?"
                : "Great! 🎉 To book a unit, you can:\n\n1. **Book online** - Quick and secure\n2. **Call us** - An advisor will help you\n3. **Visit the facility** - See the units in person\n\nWhich option do you prefer?";

            return ['message' => $booking, 'confidence' => 0.85];
        }

        if (str_contains($message, 'conseiller') || str_contains($message, 'humain') || str_contains($message, 'agent') || str_contains($message, 'parler')) {
            $transfer = $this->locale === 'fr'
                ? "Je comprends que vous souhaitez parler à un conseiller. 📞\n\nVoici vos options :\n• **Téléphone** : Notre équipe est disponible du lundi au samedi\n• **Email** : Réponse sous 24h\n• **Rappel** : Laissez vos coordonnées et nous vous rappelons\n\nQue préférez-vous ?"
                : "I understand you'd like to speak with an advisor. 📞\n\nHere are your options:\n• **Phone**: Our team is available Monday to Saturday\n• **Email**: Response within 24h\n• **Callback**: Leave your details and we'll call you back\n\nWhat would you prefer?";

            return ['message' => $transfer, 'confidence' => 0.9];
        }

        // Default fallback
        return [
            'message' => $this->getFallbackResponse(),
            'confidence' => 0.5,
        ];
    }

    /**
     * Get fallback response when we can't understand the query
     */
    protected function getFallbackResponse(): string
    {
        return $this->locale === 'fr'
            ? "Je ne suis pas sûr de bien comprendre votre question. 🤔\n\nPuis-je vous aider avec l'un de ces sujets ?\n• Tarifs et disponibilités\n• Choix de taille de box\n• Accès et horaires\n• Contrats et paiements\n\nOu souhaitez-vous parler à un conseiller ?"
            : "I'm not sure I understand your question. 🤔\n\nCan I help you with one of these topics?\n• Rates and availability\n• Choosing unit size\n• Access and hours\n• Contracts and payments\n\nOr would you like to speak with an advisor?";
    }

    /**
     * Get suggested actions based on intent
     */
    protected function getSuggestedActions(?string $intent): array
    {
        $actions = [
            'pricing_inquiry' => [
                ['label' => 'Voir les disponibilités', 'action' => 'check_availability'],
                ['label' => 'Réserver un box', 'action' => 'start_booking'],
            ],
            'availability_check' => [
                ['label' => 'Réserver maintenant', 'action' => 'start_booking'],
                ['label' => 'Voir les tarifs', 'action' => 'show_pricing'],
            ],
            'size_recommendation' => [
                ['label' => 'Calculer ma taille', 'action' => 'size_calculator'],
                ['label' => 'Voir les disponibilités', 'action' => 'check_availability'],
            ],
            'access_info' => [
                ['label' => 'Télécharger l\'app', 'action' => 'download_app'],
                ['label' => 'Mon code d\'accès', 'action' => 'show_access_code'],
            ],
            'payment_info' => [
                ['label' => 'Payer en ligne', 'action' => 'make_payment'],
                ['label' => 'Configurer SEPA', 'action' => 'setup_sepa'],
            ],
            'contract_info' => [
                ['label' => 'Mon contrat', 'action' => 'view_contract'],
                ['label' => 'Résilier', 'action' => 'terminate_contract'],
            ],
        ];

        return $actions[$intent] ?? [
            ['label' => 'Parler à un conseiller', 'action' => 'contact_advisor'],
            ['label' => 'Voir les disponibilités', 'action' => 'check_availability'],
        ];
    }

    /**
     * Log chatbot interaction for analytics
     */
    protected function logInteraction(string $message, string $response, ?string $intent, string $source, float $confidence): void
    {
        try {
            Log::channel('chatbot')->info('Chatbot interaction', [
                'tenant_id' => $this->tenantId,
                'site_id' => $this->siteId,
                'intent' => $intent,
                'source' => $source,
                'confidence' => $confidence,
                'message_length' => strlen($message),
                'response_length' => strlen($response),
            ]);
        } catch (\Exception $e) {
            // Don't let logging failures break the chatbot
        }
    }

    /**
     * Get chatbot analytics for a period
     */
    public function getAnalytics(int $tenantId, string $period = '7d'): array
    {
        // This would query from a chatbot_logs table in production
        return [
            'total_conversations' => 0,
            'total_messages' => 0,
            'faq_deflection_rate' => 0,
            'avg_confidence' => 0,
            'top_intents' => [],
            'handoff_rate' => 0,
        ];
    }

    /**
     * Recommend box size based on items description
     */
    public function recommendBoxSize(string $itemsDescription): array
    {
        $description = mb_strtolower($itemsDescription);

        // Keywords and associated size recommendations
        $sizeKeywords = [
            'small' => ['carton', 'box', 'valise', 'suitcase', 'documents', 'petit', 'small', 'quelques'],
            'medium' => ['studio', 'meuble', 'furniture', 'canapé', 'sofa', 'lit', 'bed', 'bureau', 'desk', 'chambre', 'bedroom'],
            'large' => ['appartement', 'apartment', 'maison', 'house', 'déménagement', 'moving', 'tout', 'everything', 'complet', 'full'],
        ];

        $recommendedSize = 'medium'; // default

        foreach ($sizeKeywords as $size => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($description, $keyword)) {
                    $recommendedSize = $size;
                    break 2;
                }
            }
        }

        $recommendations = [
            'small' => [
                'size_m2' => '1-3',
                'description_fr' => 'Idéal pour des cartons, petits meubles ou archives',
                'description_en' => 'Ideal for boxes, small furniture or archives',
            ],
            'medium' => [
                'size_m2' => '4-8',
                'description_fr' => 'Parfait pour le contenu d\'un studio ou d\'une chambre',
                'description_en' => 'Perfect for a studio or bedroom contents',
            ],
            'large' => [
                'size_m2' => '9-15',
                'description_fr' => 'Pour le contenu d\'un appartement ou d\'une maison',
                'description_en' => 'For apartment or house contents',
            ],
        ];

        $recommendation = $recommendations[$recommendedSize];

        // Get available boxes matching this size
        $availableBoxes = $this->getMatchingBoxes($recommendedSize);

        return [
            'recommended_size' => $recommendedSize,
            'size_m2' => $recommendation['size_m2'],
            'description' => $this->locale === 'fr'
                ? $recommendation['description_fr']
                : $recommendation['description_en'],
            'available_boxes' => $availableBoxes,
        ];
    }

    /**
     * Get available boxes matching size category
     */
    protected function getMatchingBoxes(string $sizeCategory): array
    {
        $query = Box::where('status', 'available');

        if ($this->tenantId) {
            $query->where('tenant_id', $this->tenantId);
        }
        if ($this->siteId) {
            $query->where('site_id', $this->siteId);
        }

        $sizeRanges = [
            'small' => [0, 3],
            'medium' => [3.1, 8],
            'large' => [8.1, 100],
        ];

        $range = $sizeRanges[$sizeCategory];
        $query->whereRaw('(length * width) BETWEEN ? AND ?', $range);

        return $query->orderBy('current_price')
            ->take(3)
            ->get()
            ->map(fn($box) => [
                'id' => $box->id,
                'name' => $box->name,
                'size_m2' => $box->size_m2,
                'price' => $box->current_price,
            ])
            ->toArray();
    }
}
