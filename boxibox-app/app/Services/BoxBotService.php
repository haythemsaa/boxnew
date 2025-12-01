<?php

namespace App\Services;

use App\Models\ChatbotConfiguration;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ChatbotKnowledgeBase;
use App\Models\ChatbotIntent;
use App\Models\ChatbotSizeRecommendation;
use App\Models\Box;
use App\Models\Site;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BoxBotService
{
    protected ?ChatbotConfiguration $config = null;

    // Items standards pour le calculateur de taille
    protected array $standardItems = [
        'cartons' => ['name' => 'Carton standard', 'volume' => 0.05],
        'lit_simple' => ['name' => 'Lit simple', 'volume' => 1.2],
        'lit_double' => ['name' => 'Lit double', 'volume' => 2.5],
        'armoire' => ['name' => 'Armoire', 'volume' => 1.5],
        'canape_2_places' => ['name' => 'Canapé 2 places', 'volume' => 1.8],
        'canape_3_places' => ['name' => 'Canapé 3 places', 'volume' => 2.5],
        'table_manger' => ['name' => 'Table à manger', 'volume' => 0.8],
        'chaise' => ['name' => 'Chaise', 'volume' => 0.2],
        'bureau' => ['name' => 'Bureau', 'volume' => 0.6],
        'machine_laver' => ['name' => 'Machine à laver', 'volume' => 0.5],
        'refrigerateur' => ['name' => 'Réfrigérateur', 'volume' => 0.8],
        'television' => ['name' => 'Télévision', 'volume' => 0.2],
        'velo' => ['name' => 'Vélo', 'volume' => 0.3],
        'matelas' => ['name' => 'Matelas', 'volume' => 0.5],
    ];

    /**
     * Initialiser une nouvelle conversation
     */
    public function startConversation(int $tenantId, array $visitorInfo = []): ChatbotConversation
    {
        $this->loadConfig($tenantId);

        return ChatbotConversation::create([
            'tenant_id' => $tenantId,
            'session_id' => Str::uuid(),
            'channel' => $visitorInfo['channel'] ?? 'web',
            'language' => $visitorInfo['language'] ?? $this->config?->default_language ?? 'fr',
            'visitor_name' => $visitorInfo['name'] ?? null,
            'visitor_email' => $visitorInfo['email'] ?? null,
            'visitor_phone' => $visitorInfo['phone'] ?? null,
            'ip_address' => $visitorInfo['ip'] ?? null,
            'user_agent' => $visitorInfo['user_agent'] ?? null,
            'referrer' => $visitorInfo['referrer'] ?? null,
            'status' => 'active',
            'last_message_at' => now(),
        ]);
    }

    /**
     * Traiter un message utilisateur
     */
    public function processMessage(ChatbotConversation $conversation, string $message): array
    {
        $this->loadConfig($conversation->tenant_id);

        // Sauvegarder le message utilisateur
        $userMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'content' => $message,
            'content_type' => 'text',
        ]);

        $conversation->increment('message_count');
        $conversation->update(['last_message_at' => now()]);

        // Détecter l'intention
        $intent = $this->detectIntent($message, $conversation);

        // Générer la réponse
        $response = $this->generateResponse($intent, $message, $conversation);

        // Sauvegarder la réponse du bot
        $botMessage = ChatbotMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'bot',
            'content' => $response['text'],
            'content_type' => $response['type'] ?? 'text',
            'quick_replies' => $response['quick_replies'] ?? null,
            'buttons' => $response['buttons'] ?? null,
            'intent_detected' => $intent['name'],
            'confidence_score' => $intent['confidence'],
            'knowledge_base_id' => $response['kb_id'] ?? null,
        ]);

        return [
            'message' => $response['text'],
            'type' => $response['type'] ?? 'text',
            'quick_replies' => $response['quick_replies'] ?? [],
            'buttons' => $response['buttons'] ?? [],
            'data' => $response['data'] ?? null,
        ];
    }

    /**
     * Détecter l'intention du message
     */
    protected function detectIntent(string $message, ChatbotConversation $conversation): array
    {
        $message = strtolower(trim($message));

        // Patterns d'intentions
        $patterns = [
            'greeting' => ['bonjour', 'salut', 'hello', 'bonsoir', 'coucou', 'hey'],
            'pricing' => ['prix', 'tarif', 'combien', 'coût', 'coute', 'cher', 'moins cher', 'promotion'],
            'availability' => ['disponible', 'disponibilité', 'libre', 'place', 'stock'],
            'booking' => ['réserver', 'reserver', 'louer', 'location', 'prendre un box'],
            'size_help' => ['taille', 'quelle surface', 'combien de m²', 'm2', 'mètres carrés', 'besoin', 'déménagement'],
            'location' => ['où', 'adresse', 'situé', 'emplacement', 'accès', 'comment venir'],
            'hours' => ['horaire', 'ouvert', 'fermé', 'heure', 'accès 24h'],
            'payment' => ['paiement', 'payer', 'carte', 'prélèvement', 'facture', 'sepa'],
            'contract' => ['contrat', 'durée', 'engagement', 'préavis', 'résiliation'],
            'security' => ['sécurité', 'surveillance', 'alarme', 'caméra', 'assurance'],
            'human' => ['humain', 'conseiller', 'parler à quelqu\'un', 'téléphone', 'rappeler'],
            'thanks' => ['merci', 'super', 'parfait', 'génial', 'au revoir', 'bye'],
        ];

        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    return [
                        'name' => $intent,
                        'confidence' => 0.85,
                        'keyword_matched' => $keyword,
                    ];
                }
            }
        }

        return ['name' => 'unknown', 'confidence' => 0.3];
    }

    /**
     * Générer une réponse basée sur l'intention
     */
    protected function generateResponse(array $intent, string $message, ChatbotConversation $conversation): array
    {
        $tenantId = $conversation->tenant_id;

        switch ($intent['name']) {
            case 'greeting':
                return [
                    'text' => $this->config?->welcome_message ?? "Bonjour ! Je suis BoxBot, votre assistant virtuel. Comment puis-je vous aider ?",
                    'quick_replies' => [
                        ['label' => 'Voir les tarifs', 'value' => 'tarifs'],
                        ['label' => 'Calculer ma taille', 'value' => 'calculateur'],
                        ['label' => 'Disponibilités', 'value' => 'disponibilite'],
                        ['label' => 'Parler à un conseiller', 'value' => 'humain'],
                    ],
                ];

            case 'pricing':
                $prices = $this->getPriceRanges($tenantId);
                return [
                    'text' => "**Nos tarifs** :\n\n" . $prices . "\n\nLes prix varient selon la taille et la durée. Souhaitez-vous un devis ?",
                    'quick_replies' => [
                        ['label' => 'Calculer ma taille', 'value' => 'calculateur'],
                        ['label' => 'Réserver maintenant', 'value' => 'reserver'],
                    ],
                ];

            case 'availability':
                $availability = $this->getAvailability($tenantId);
                return [
                    'text' => "**Disponibilités actuelles** :\n\n" . $availability,
                    'quick_replies' => [
                        ['label' => 'Réserver', 'value' => 'reserver'],
                        ['label' => 'Être rappelé', 'value' => 'callback'],
                    ],
                ];

            case 'size_help':
                return [
                    'text' => "Je peux vous aider à trouver la taille idéale ! Qu'allez-vous stocker ?",
                    'quick_replies' => [
                        ['label' => 'Cartons uniquement', 'value' => 'cartons'],
                        ['label' => 'Studio', 'value' => 'studio'],
                        ['label' => 'Appartement', 'value' => 'appartement'],
                        ['label' => 'Maison', 'value' => 'maison'],
                    ],
                ];

            case 'booking':
                $conversation->update(['current_intent' => 'booking']);
                return [
                    'text' => "Excellente décision ! Quelle surface recherchez-vous ?",
                    'quick_replies' => [
                        ['label' => '1-3 m²', 'value' => 'size_small'],
                        ['label' => '4-8 m²', 'value' => 'size_medium'],
                        ['label' => '9-15 m²', 'value' => 'size_large'],
                        ['label' => '+ de 15 m²', 'value' => 'size_xlarge'],
                    ],
                ];

            case 'hours':
                return [
                    'text' => "**Horaires d'accès** :\n\n- Accès 7j/7 de 6h à 22h\n- Accès 24h/24 sur demande\n- Bureau : lundi-samedi, 9h-18h",
                ];

            case 'security':
                return [
                    'text' => "**Sécurité maximale** :\n\n- Vidéosurveillance 24h/24\n- Alarme individuelle\n- Code personnel\n- Site clôturé\n- Assurance disponible",
                ];

            case 'human':
                $conversation->update(['status' => 'waiting_human', 'transferred_at' => now()]);
                return [
                    'text' => "Je vous mets en relation avec un conseiller. Laissez-nous votre nom et numéro.",
                    'type' => 'form',
                ];

            case 'thanks':
                return ['text' => "Avec plaisir ! N'hésitez pas si vous avez d'autres questions."];

            default:
                $kbAnswer = $this->searchKnowledgeBase($message, $tenantId);
                if ($kbAnswer) {
                    return ['text' => $kbAnswer['answer'], 'kb_id' => $kbAnswer['id']];
                }
                return [
                    'text' => "Je ne suis pas sûr de comprendre. Comment puis-je vous aider ?",
                    'quick_replies' => [
                        ['label' => 'Tarifs', 'value' => 'tarifs'],
                        ['label' => 'Taille de box', 'value' => 'taille'],
                        ['label' => 'Parler à un humain', 'value' => 'humain'],
                    ],
                ];
        }
    }

    /**
     * Calculer la taille recommandée
     */
    public function calculateRecommendedSize(array $items): array
    {
        $totalVolume = 0;
        foreach ($items as $itemKey => $quantity) {
            if (isset($this->standardItems[$itemKey])) {
                $totalVolume += $this->standardItems[$itemKey]['volume'] * $quantity;
            }
        }

        $totalVolume *= 1.2; // +20% marge
        $surfaceNeeded = $totalVolume / 2.5;

        $boxSize = match (true) {
            $surfaceNeeded <= 1 => ['size' => 1, 'label' => '1 m²', 'type' => 'XS'],
            $surfaceNeeded <= 2 => ['size' => 2, 'label' => '2 m²', 'type' => 'S'],
            $surfaceNeeded <= 4 => ['size' => 4, 'label' => '4 m²', 'type' => 'M'],
            $surfaceNeeded <= 6 => ['size' => 6, 'label' => '6 m²', 'type' => 'L'],
            $surfaceNeeded <= 10 => ['size' => 10, 'label' => '10 m²', 'type' => 'XL'],
            $surfaceNeeded <= 15 => ['size' => 15, 'label' => '15 m²', 'type' => 'XXL'],
            default => ['size' => 20, 'label' => '20+ m²', 'type' => 'XXXL'],
        };

        return [
            'total_volume' => round($totalVolume, 2),
            'surface_needed' => round($surfaceNeeded, 2),
            'recommended_size' => $boxSize['size'],
            'recommended_label' => $boxSize['label'],
            'box_type' => $boxSize['type'],
        ];
    }

    protected function getPriceRanges(int $tenantId): string
    {
        $boxes = Box::whereHas('site', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('status', '!=', 'maintenance')
            ->get();

        if ($boxes->isEmpty()) {
            return "Contactez-nous pour un devis.";
        }

        $bySize = $boxes->groupBy('box_type');
        $lines = [];
        foreach ($bySize as $type => $typeBoxes) {
            $minPrice = $typeBoxes->min('monthly_price');
            $lines[] = "- **{$type}** : à partir de {$minPrice}€/mois";
        }
        return implode("\n", $lines);
    }

    protected function getAvailability(int $tenantId): string
    {
        $available = Box::whereHas('site', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('status', 'available')
            ->selectRaw('box_type, COUNT(*) as count')
            ->groupBy('box_type')
            ->get();

        if ($available->isEmpty()) {
            return "Peu de disponibilités. Contactez-nous.";
        }

        $lines = [];
        foreach ($available as $row) {
            $lines[] = "- {$row->box_type} : {$row->count} disponible(s)";
        }
        return implode("\n", $lines);
    }

    protected function searchKnowledgeBase(string $query, int $tenantId): ?array
    {
        $result = ChatbotKnowledgeBase::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('question', 'LIKE', "%{$query}%")
                    ->orWhere('answer', 'LIKE', "%{$query}%");
            })
            ->first();

        if ($result) {
            $result->increment('usage_count');
            return $result->toArray();
        }
        return null;
    }

    protected function loadConfig(int $tenantId): void
    {
        $this->config = ChatbotConfiguration::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public function createLeadFromConversation(ChatbotConversation $conversation): ?Lead
    {
        if (!$conversation->visitor_email && !$conversation->visitor_phone) {
            return null;
        }

        $lead = Lead::create([
            'tenant_id' => $conversation->tenant_id,
            'source' => 'chatbot',
            'name' => $conversation->visitor_name ?? 'Visiteur Chatbot',
            'email' => $conversation->visitor_email,
            'phone' => $conversation->visitor_phone,
            'status' => 'new',
        ]);

        $conversation->update(['converted_to_lead' => true]);
        return $lead;
    }
}
