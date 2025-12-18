<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected AIService $aiService;
    protected array $knowledgeBase;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
        $this->knowledgeBase = $this->loadKnowledgeBase();
    }

    /**
     * Get current AI provider info
     */
    public function getProviderInfo(): array
    {
        return $this->aiService->getProviderInfo();
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
        $conversationContext = $this->buildConversationContext($conversation);

        // Build business context
        $businessContext = $this->buildBusinessContext($tenantId);

        // Enhance message with system prompt
        $enhancedMessage = $this->getSystemPrompt() . "\n\nHistorique de conversation:\n" . $conversationContext . "\n\nMessage du visiteur: " . $message;

        // Call AI Service (Groq, Gemini, etc.)
        $aiResponse = $this->aiService->chat($enhancedMessage, $businessContext);

        // Store AI response
        $botMessage = $this->storeMessage($conversation, 'assistant', $aiResponse['message']);

        // Extract intent and entities
        $intent = $this->detectIntent($message);
        $entities = $this->extractEntities($message);

        // Auto-actions based on intent
        $this->handleIntent($intent, $entities, $conversation);

        return [
            'conversation_id' => $conversation->id,
            'message' => $aiResponse['message'],
            'intent' => $intent,
            'entities' => $entities,
            'suggestions' => $this->generateSuggestions($intent),
            'provider' => $aiResponse['provider'] ?? 'unknown',
        ];
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
     * Build conversation context string
     */
    protected function buildConversationContext(Conversation $conversation): string
    {
        $messages = $conversation->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse();

        $context = '';
        foreach ($messages as $msg) {
            $role = $msg->sender_type === 'user' ? 'Visiteur' : 'Assistant';
            $context .= "{$role}: {$msg->content}\n";
        }

        return $context;
    }

    /**
     * Build business context for AI
     */
    protected function buildBusinessContext(?int $tenantId): array
    {
        if (!$tenantId) {
            return [];
        }

        try {
            return [
                'available_boxes' => \App\Models\Box::where('tenant_id', $tenantId)->where('status', 'available')->count(),
                'total_boxes' => \App\Models\Box::where('tenant_id', $tenantId)->count(),
            ];
        } catch (\Exception $e) {
            return [];
        }
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
                if (isset($entities['email']) || isset($entities['phone'])) {
                    $this->createLeadFromConversation($conversation, $entities);
                }
                break;

            case 'visit':
                $conversation->update(['requires_appointment' => true]);
                break;

            case 'pricing':
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
            'score' => 60,
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
        ];
    }

    /**
     * Calculate recommended box size based on items
     */
    public function recommendBoxSize(array $items): array
    {
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
