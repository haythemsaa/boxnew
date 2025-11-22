<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected string $apiKey;
    protected string $model;
    protected array $knowledgeBase;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $this->model = 'gpt-4';
        $this->knowledgeBase = $this->loadKnowledgeBase();
    }

    /**
     * Process user message and generate AI response
     */
    public function chat(string $message, ?int $conversationId = null, ?int $tenantId = null): array
    {
        // Get or create conversation
        $conversation = $conversationId
            ? Conversation::find($conversationId)
            : $this->createConversation($tenantId);

        // Store user message
        $userMessage = $this->storeMessage($conversation, 'user', $message);

        // Build context from conversation history
        $context = $this->buildContext($conversation);

        // Call OpenAI API
        $aiResponse = $this->callOpenAI($context, $message);

        // Store AI response
        $botMessage = $this->storeMessage($conversation, 'assistant', $aiResponse['content']);

        // Extract intent and entities
        $intent = $this->detectIntent($message);
        $entities = $this->extractEntities($message);

        // Auto-actions based on intent
        $this->handleIntent($intent, $entities, $conversation);

        return [
            'conversation_id' => $conversation->id,
            'message' => $aiResponse['content'],
            'intent' => $intent,
            'entities' => $entities,
            'suggestions' => $this->generateSuggestions($intent),
        ];
    }

    /**
     * Call OpenAI GPT-4 API
     */
    protected function callOpenAI(array $context, string $userMessage): array
    {
        if (!$this->apiKey) {
            // Fallback response when no API key configured
            return $this->fallbackResponse($userMessage);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => array_merge([
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                ], $context, [
                    [
                        'role' => 'user',
                        'content' => $userMessage,
                    ],
                ]),
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'content' => $data['choices'][0]['message']['content'] ?? 'Désolé, je n\'ai pas compris.',
                    'usage' => $data['usage'] ?? [],
                ];
            }

            Log::error('OpenAI API Error', ['response' => $response->body()]);
            return $this->fallbackResponse($userMessage);

        } catch (\Exception $e) {
            Log::error('Chatbot Exception', ['error' => $e->getMessage()]);
            return $this->fallbackResponse($userMessage);
        }
    }

    /**
     * System prompt for the chatbot
     */
    protected function getSystemPrompt(): string
    {
        return "Tu es un assistant virtuel intelligent pour Boxibox, une plateforme de gestion de self-storage (location de boxes de stockage).

Ta mission est d'aider les visiteurs et clients à:
- Trouver la taille de box adaptée à leurs besoins
- Calculer un prix estimé
- Répondre aux questions fréquentes sur le self-storage
- Prendre rendez-vous
- Gérer les objections courantes

Ton style:
- Amical et professionnel
- Concis (2-3 phrases max)
- Orienté conversion (toujours proposer une action)
- Empathique avec les besoins du client

Base de connaissances:
- Petites boxes (1-3m²): vêtements, archives, ski = 40-60€/mois
- Moyennes boxes (4-7m²): mobilier studio, déménagement = 80-120€/mois
- Grandes boxes (8-15m²): mobilier F3, stock entreprise = 150-300€/mois
- Accès 24/7, sécurisé, boxes climatisées disponibles
- Premier mois à -50% pour nouveaux clients
- Assurance optionnelle 10€/mois
- Engagement minimum: 1 mois
- Résiliation: préavis de 1 mois

Si le visiteur est intéressé, propose toujours de:
1. Calculer un devis personnalisé
2. Visiter un site
3. Réserver en ligne

Réponds toujours en français.";
    }

    /**
     * Fallback response when API is not available
     */
    protected function fallbackResponse(string $message): array
    {
        // Rule-based fallback responses
        $message = strtolower($message);

        if (str_contains($message, 'prix') || str_contains($message, 'tarif') || str_contains($message, 'coût')) {
            return [
                'content' => "Nos tarifs varient selon la taille:\n• Petites boxes (1-3m²): 40-60€/mois\n• Moyennes boxes (4-7m²): 80-120€/mois\n• Grandes boxes (8-15m²): 150-300€/mois\n\n🎁 Premier mois à -50% pour nouveaux clients!\n\nQuelle taille vous intéresserait?",
            ];
        }

        if (str_contains($message, 'taille') || str_contains($message, 'm²') || str_contains($message, 'espace')) {
            return [
                'content' => "Pour vous conseiller, dites-moi ce que vous souhaitez stocker?\n\nExemples:\n• Vêtements/archives → Petite box (1-3m²)\n• Mobilier studio/F2 → Moyenne box (4-7m²)\n• Mobilier F3+ ou stock → Grande box (8-15m²)",
            ];
        }

        if (str_contains($message, 'visite') || str_contains($message, 'rdv') || str_contains($message, 'rendez-vous')) {
            return [
                'content' => "Excellente idée! Les visites permettent de mieux visualiser les espaces.\n\nJe peux vous proposer un créneau. Quel jour vous conviendrait le mieux?",
            ];
        }

        if (str_contains($message, 'réserv') || str_contains($message, 'louer') || str_contains($message, 'location')) {
            return [
                'content' => "Super! La réservation en ligne est simple et rapide (3 min).\n\nVous bénéficiez de:\n✅ -50% sur le 1er mois\n✅ Accès immédiat\n✅ Aucun engagement\n\nSouhaitez-vous que je vous guide pour réserver?",
            ];
        }

        // Default response
        return [
            'content' => "Merci pour votre message! Je suis là pour vous aider avec:\n\n• Trouver la taille de box idéale\n• Calculer un devis personnalisé\n• Prendre rendez-vous pour une visite\n• Réserver en ligne\n\nQue puis-je faire pour vous?",
        ];
    }

    /**
     * Detect user intent
     */
    protected function detectIntent(string $message): string
    {
        $message = strtolower($message);

        $intents = [
            'pricing' => ['prix', 'tarif', 'coût', 'combien', '€'],
            'sizing' => ['taille', 'm²', 'dimension', 'espace', 'grand', 'petit'],
            'booking' => ['réserv', 'louer', 'location', 'prendre'],
            'visit' => ['visite', 'voir', 'rendez-vous', 'rdv'],
            'info' => ['comment', 'pourquoi', 'qu\'est-ce', 'c\'est quoi'],
            'objection' => ['cher', 'trop', 'pas sûr', 'hésit'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'general';
    }

    /**
     * Extract entities from message
     */
    protected function extractEntities(string $message): array
    {
        $entities = [];

        // Extract size mentions
        if (preg_match('/(\d+)\s*m[²2]/i', $message, $matches)) {
            $entities['size'] = (int)$matches[1];
        }

        // Extract price mentions
        if (preg_match('/(\d+)\s*€/i', $message, $matches)) {
            $entities['price'] = (int)$matches[1];
        }

        // Extract email
        if (preg_match('/([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+)/', $message, $matches)) {
            $entities['email'] = $matches[1];
        }

        // Extract phone
        if (preg_match('/(0[1-9][\s.-]?(?:\d{2}[\s.-]?){4})/', $message, $matches)) {
            $entities['phone'] = $matches[1];
        }

        return $entities;
    }

    /**
     * Handle intent-based actions
     */
    protected function handleIntent(string $intent, array $entities, Conversation $conversation): void
    {
        switch ($intent) {
            case 'booking':
                // Create lead if email/phone provided
                if (isset($entities['email']) || isset($entities['phone'])) {
                    $this->createLeadFromConversation($conversation, $entities);
                }
                break;

            case 'visit':
                // Mark conversation as requiring appointment
                $conversation->update(['requires_appointment' => true]);
                break;

            case 'pricing':
                // Track pricing interest
                $conversation->update(['interested_in_pricing' => true]);
                break;
        }
    }

    /**
     * Create lead from chatbot conversation
     */
    protected function createLeadFromConversation(Conversation $conversation, array $entities): ?Lead
    {
        if (!isset($entities['email']) && !isset($entities['phone'])) {
            return null;
        }

        $lead = Lead::create([
            'tenant_id' => $conversation->tenant_id,
            'name' => $conversation->visitor_name ?? 'Chatbot Lead',
            'email' => $entities['email'] ?? null,
            'phone' => $entities['phone'] ?? null,
            'source' => 'chatbot',
            'status' => 'new',
            'score' => 60, // Medium score for chatbot leads
            'notes' => 'Lead créé automatiquement via chatbot',
        ]);

        $conversation->update(['lead_id' => $lead->id]);

        return $lead;
    }

    /**
     * Generate suggested responses
     */
    protected function generateSuggestions(string $intent): array
    {
        $suggestions = [
            'pricing' => [
                'Calculer mon devis personnalisé',
                'Voir les tailles disponibles',
                'Quelle promotion en cours?',
            ],
            'sizing' => [
                'Petite box (1-3m²)',
                'Moyenne box (4-7m²)',
                'Grande box (8-15m²)',
            ],
            'booking' => [
                'Réserver en ligne',
                'Prendre rendez-vous',
                'Voir les disponibilités',
            ],
            'visit' => [
                'Demain matin',
                'Cette semaine',
                'Je préfère un samedi',
            ],
            'general' => [
                'Voir les prix',
                'Quelle taille me faut-il?',
                'Prendre rendez-vous',
            ],
        ];

        return $suggestions[$intent] ?? $suggestions['general'];
    }

    /**
     * Build conversation context
     */
    protected function buildContext(Conversation $conversation): array
    {
        return $conversation->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'role' => $message->sender_type === 'user' ? 'user' : 'assistant',
                    'content' => $message->content,
                ];
            })
            ->toArray();
    }

    /**
     * Create new conversation
     */
    protected function createConversation(?int $tenantId): Conversation
    {
        return Conversation::create([
            'tenant_id' => $tenantId,
            'channel' => 'chatbot',
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Store message
     */
    protected function storeMessage(Conversation $conversation, string $role, string $content): Message
    {
        return $conversation->messages()->create([
            'sender_type' => $role,
            'content' => $content,
            'sent_at' => now(),
        ]);
    }

    /**
     * Load knowledge base
     */
    protected function loadKnowledgeBase(): array
    {
        return [
            'box_sizes' => [
                'small' => ['min' => 1, 'max' => 3, 'price' => [40, 60], 'use_cases' => ['Vêtements', 'Archives', 'Équipement sportif']],
                'medium' => ['min' => 4, 'max' => 7, 'price' => [80, 120], 'use_cases' => ['Studio', 'F2', 'Déménagement']],
                'large' => ['min' => 8, 'max' => 15, 'price' => [150, 300], 'use_cases' => ['F3+', 'Stock entreprise', 'Mobilier complet']],
            ],
            'features' => [
                'Accès 24/7',
                'Sécurité renforcée',
                'Boxes climatisées disponibles',
                'Assurance optionnelle',
                'Premier mois -50%',
                'Sans engagement',
            ],
            'faqs' => [
                'Puis-je résilier quand je veux?' => 'Oui, sans engagement. Préavis de 1 mois seulement.',
                'L\'assurance est-elle obligatoire?' => 'Non, elle est optionnelle à 10€/mois.',
                'Puis-je accéder 24/7?' => 'Oui, accès illimité avec votre code personnel.',
                'Y a-t-il des frais cachés?' => 'Non, tarif tout compris. Vous payez seulement le loyer mensuel.',
            ],
        ];
    }

    /**
     * Calculate recommended box size based on items
     */
    public function recommendBoxSize(array $items): array
    {
        // Simple algorithm based on item types
        $volumeEstimate = 0;

        $itemVolumes = [
            'mobilier_complet' => 12,
            'mobilier_partiel' => 6,
            'cartons' => 0.5,
            'vetements' => 1,
            'archives' => 2,
            'equipement_sport' => 1.5,
        ];

        foreach ($items as $item => $quantity) {
            $volumeEstimate += ($itemVolumes[$item] ?? 1) * $quantity;
        }

        if ($volumeEstimate <= 3) {
            return ['size' => 'small', 'sqm' => '1-3m²', 'price' => '40-60€/mois'];
        } elseif ($volumeEstimate <= 7) {
            return ['size' => 'medium', 'sqm' => '4-7m²', 'price' => '80-120€/mois'];
        } else {
            return ['size' => 'large', 'sqm' => '8-15m²', 'price' => '150-300€/mois'];
        }
    }
}
