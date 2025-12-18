<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        // Priority: Groq (fast & free) > Gemini (free) > OpenRouter > Fallback
        $this->provider = $this->detectBestProvider();
        $this->config = $this->getProviderConfig();
    }

    /**
     * Detect the best available AI provider
     */
    protected function detectBestProvider(): string
    {
        if (config('services.groq.api_key')) {
            return 'groq';
        }
        if (config('services.gemini.api_key')) {
            return 'gemini';
        }
        if (config('services.openrouter.api_key')) {
            return 'openrouter';
        }
        if (config('services.openai.api_key')) {
            return 'openai';
        }
        return 'fallback';
    }

    /**
     * Get provider configuration
     */
    protected function getProviderConfig(): array
    {
        return match ($this->provider) {
            'groq' => [
                'url' => 'https://api.groq.com/openai/v1/chat/completions',
                'key' => config('services.groq.api_key'),
                'model' => config('services.groq.model', 'llama-3.3-70b-versatile'),
            ],
            'gemini' => [
                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
                'key' => config('services.gemini.api_key'),
                'model' => 'gemini-1.5-flash',
            ],
            'openrouter' => [
                'url' => 'https://openrouter.ai/api/v1/chat/completions',
                'key' => config('services.openrouter.api_key'),
                'model' => config('services.openrouter.model', 'meta-llama/llama-3.1-8b-instruct:free'),
            ],
            'openai' => [
                'url' => 'https://api.openai.com/v1/chat/completions',
                'key' => config('services.openai.api_key'),
                'model' => config('services.openai.model', 'gpt-3.5-turbo'),
            ],
            default => [
                'url' => null,
                'key' => null,
                'model' => null,
            ],
        };
    }

    /**
     * Chat with AI
     */
    public function chat(string $message, array $context = [], array $options = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($context);

        try {
            $response = match ($this->provider) {
                'groq' => $this->chatWithGroq($systemPrompt, $message, $options),
                'gemini' => $this->chatWithGemini($systemPrompt, $message, $options),
                'openrouter' => $this->chatWithOpenRouter($systemPrompt, $message, $options),
                'openai' => $this->chatWithOpenAI($systemPrompt, $message, $options),
                default => $this->fallbackResponse($message, $context),
            };

            return [
                'success' => true,
                'message' => $response,
                'provider' => $this->provider,
            ];
        } catch (\Exception $e) {
            Log::error('AI Service Error', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            // Fallback to local response
            return [
                'success' => false,
                'message' => $this->fallbackResponse($message, $context),
                'provider' => 'fallback',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build system prompt with business context
     */
    protected function buildSystemPrompt(array $context): string
    {
        $basePrompt = <<<PROMPT
Tu es BoxiBox AI, un assistant intelligent EXPERT spécialisé dans la gestion de self-storage (garde-meubles).
Tu travailles pour une entreprise de location de boxes de stockage.

RÈGLES IMPORTANTES:
1. Réponds TOUJOURS en français
2. Sois professionnel mais amical
3. Utilise le formatage Markdown pour structurer tes réponses
4. Utilise **gras** pour les éléments importants
5. Utilise des listes à puces (•) pour les énumérations
6. Si tu donnes des chiffres, utilise le format français (ex: 1 234,56 €)
7. Sois concis mais complet
8. Propose toujours des actions ou suggestions à la fin de ta réponse
9. Pour les contenus marketing, fournis des EXEMPLES CONCRETS prêts à l'emploi

DOMAINES D'EXPERTISE:
- Gestion des boxes de stockage (occupation, disponibilité, tarifs)
- Gestion des clients et contrats
- Facturation et paiements
- Relances et impayés
- Statistiques et KPIs
- Optimisation tarifaire
- Prédiction de churn
- Conseils business pour le self-storage

EXPERTISE MARKETING DIGITAL:
📧 EMAIL MARKETING: Rédaction d'emails commerciaux, objets accrocheurs, séquences automatisées
📱 SMS MARKETING: Messages courts et percutants, campagnes promotionnelles, timing optimal
📢 PUBLICITÉS: Google Ads, Facebook Ads, Instagram - accroches, textes, call-to-action
🌐 SITE WEB: UX/UI, pages de landing, optimisation conversion, SEO local
🚀 GROWTH HACKING: Parrainage, fidélisation, upselling, promotions saisonnières

PROMPT;

        // Add business context if available
        if (!empty($context)) {
            $basePrompt .= "\n\nCONTEXTE BUSINESS ACTUEL:\n";

            if (isset($context['occupation_rate'])) {
                $basePrompt .= "- Taux d'occupation: {$context['occupation_rate']}%\n";
            }
            if (isset($context['total_boxes'])) {
                $basePrompt .= "- Total boxes: {$context['total_boxes']} (Occupés: {$context['occupied_boxes']}, Disponibles: {$context['available_boxes']})\n";
            }
            if (isset($context['revenue'])) {
                $basePrompt .= "- Chiffre d'affaires du mois: " . number_format($context['revenue'], 0, ',', ' ') . " €\n";
            }
            if (isset($context['unpaid_invoices'])) {
                $basePrompt .= "- Factures impayées: {$context['unpaid_invoices']} pour " . number_format($context['unpaid_amount'], 0, ',', ' ') . " €\n";
            }
            if (isset($context['active_customers'])) {
                $basePrompt .= "- Clients actifs: {$context['active_customers']}\n";
            }
            if (isset($context['expiring_contracts'])) {
                $basePrompt .= "- Contrats expirant dans 30 jours: {$context['expiring_contracts']}\n";
            }
        }

        return $basePrompt;
    }

    /**
     * Chat with Groq API (Free, fast)
     */
    protected function chatWithGroq(string $systemPrompt, string $message, array $options): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['key'],
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->config['url'], [
            'model' => $this->config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2048,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Groq API Error: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    /**
     * Chat with Google Gemini API (Free tier: 60 RPM)
     */
    protected function chatWithGemini(string $systemPrompt, string $message, array $options): string
    {
        $url = $this->config['url'] . '?key=' . $this->config['key'];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($url, [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nQuestion de l'utilisateur: " . $message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API Error: ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    /**
     * Chat with OpenRouter API (Multiple free models)
     */
    protected function chatWithOpenRouter(string $systemPrompt, string $message, array $options): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['key'],
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'BoxiBox AI',
        ])->timeout(30)->post($this->config['url'], [
            'model' => $this->config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2048,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API Error: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    /**
     * Chat with OpenAI API
     */
    protected function chatWithOpenAI(string $systemPrompt, string $message, array $options): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['key'],
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->config['url'], [
            'model' => $this->config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2048,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API Error: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    /**
     * Fallback response when no AI provider is available
     */
    protected function fallbackResponse(string $message, array $context): string
    {
        $lowerMessage = strtolower($message);

        // Simple keyword-based responses
        if (str_contains($lowerMessage, 'bonjour') || str_contains($lowerMessage, 'salut') || str_contains($lowerMessage, 'hello')) {
            return "Bonjour ! 👋 Je suis BoxiBox AI, votre assistant intelligent.\n\n" .
                "Je peux vous aider avec:\n" .
                "• **Statistiques** - Occupation, revenus, KPIs\n" .
                "• **Factures** - Impayés, relances\n" .
                "• **Clients** - Gestion, risque de churn\n" .
                "• **Optimisation** - Tarifs, occupation\n\n" .
                "Comment puis-je vous aider aujourd'hui ?";
        }

        if (str_contains($lowerMessage, 'occupation') || str_contains($lowerMessage, 'box')) {
            $rate = $context['occupation_rate'] ?? 'N/A';
            $total = $context['total_boxes'] ?? 'N/A';
            $occupied = $context['occupied_boxes'] ?? 'N/A';

            return "**État de l'occupation**\n\n" .
                "• Taux d'occupation: **{$rate}%**\n" .
                "• Boxes occupés: **{$occupied}** / {$total}\n" .
                "• Boxes disponibles: **" . (($context['available_boxes'] ?? 0)) . "**\n\n" .
                "Souhaitez-vous plus de détails par site ou par taille de box ?";
        }

        if (str_contains($lowerMessage, 'facture') || str_contains($lowerMessage, 'impayé') || str_contains($lowerMessage, 'paiement')) {
            $unpaid = $context['unpaid_invoices'] ?? 0;
            $amount = number_format($context['unpaid_amount'] ?? 0, 0, ',', ' ');

            return "**État des impayés**\n\n" .
                "• Factures en attente: **{$unpaid}**\n" .
                "• Montant total: **{$amount} €**\n\n" .
                "Voulez-vous voir le détail ou lancer une campagne de relance ?";
        }

        if (str_contains($lowerMessage, 'chiffre') || str_contains($lowerMessage, 'revenu') || str_contains($lowerMessage, 'ca')) {
            $revenue = number_format($context['revenue'] ?? 0, 0, ',', ' ');

            return "**Chiffre d'affaires**\n\n" .
                "• CA du mois: **{$revenue} €**\n\n" .
                "Je peux vous donner plus de détails sur la répartition des revenus si vous le souhaitez.";
        }

        if (str_contains($lowerMessage, 'aide') || str_contains($lowerMessage, 'help')) {
            return "**Aide - BoxiBox AI**\n\n" .
                "Je suis votre assistant intelligent pour la gestion de self-storage.\n\n" .
                "**Questions que vous pouvez me poser:**\n" .
                "• \"Quel est mon taux d'occupation ?\"\n" .
                "• \"Combien ai-je de factures impayées ?\"\n" .
                "• \"Donne-moi le briefing du jour\"\n" .
                "• \"Quels clients risquent de partir ?\"\n" .
                "• \"Comment optimiser mes tarifs ?\"\n\n" .
                "**Ou posez n'importe quelle question** sur votre activité !";
        }

        // Default response
        return "Je comprends votre demande concernant \"" . substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '') . "\".\n\n" .
            "Pour vous donner une réponse précise, je vous invite à:\n" .
            "• Poser une question spécifique sur vos **boxes**, **clients**, **factures** ou **statistiques**\n" .
            "• Ou configurer une clé API (Groq, Gemini, ou OpenRouter) pour des réponses IA complètes\n\n" .
            "**Configuration API:**\n" .
            "Ajoutez dans votre fichier `.env`:\n" .
            "```\n" .
            "GROQ_API_KEY=votre_cle_gratuite\n" .
            "```\n" .
            "Obtenez une clé gratuite sur [console.groq.com](https://console.groq.com)";
    }

    /**
     * Get current provider info
     */
    public function getProviderInfo(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->config['model'] ?? 'fallback',
            'has_api_key' => !empty($this->config['key']),
        ];
    }
}
