<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\AIChatbotService;
use App\Services\AIService;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    protected AIChatbotService $chatbotService;
    protected AIService $aiService;

    public function __construct(AIChatbotService $chatbotService, AIService $aiService)
    {
        $this->chatbotService = $chatbotService;
        $this->aiService = $aiService;
    }

    /**
     * Get AI provider info
     *
     * @return JsonResponse
     */
    public function getProviderInfo(): JsonResponse
    {
        return response()->json($this->aiService->getProviderInfo());
    }

    /**
     * Send a message to the chatbot
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|string|max:50',
            'tenant_id' => 'required|integer',
            'site_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'locale' => 'nullable|string|in:fr,en',
        ]);

        $conversationId = $request->input('conversation_id') ?? Str::uuid()->toString();
        $locale = $request->input('locale', 'fr');

        // Configure the chatbot service
        $this->chatbotService->setContext(
            $request->input('tenant_id'),
            $request->input('site_id'),
            $locale
        );

        // Store user message
        $this->storeMessage($conversationId, 'user', $request->input('message'), [
            'tenant_id' => $request->input('tenant_id'),
            'site_id' => $request->input('site_id'),
            'customer_id' => $request->input('customer_id'),
        ]);

        // Process message and get response
        $response = $this->chatbotService->processMessage(
            $request->input('message'),
            $conversationId,
            $request->input('customer_id')
        );

        // Store assistant response
        $this->storeMessage($conversationId, 'assistant', $response['message'], [
            'tenant_id' => $request->input('tenant_id'),
            'intent' => $response['intent'],
            'source' => $response['source'],
            'confidence' => $response['confidence'],
        ]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversationId,
            'response' => $response['message'],
            'intent' => $response['intent'],
            'confidence' => $response['confidence'],
            'suggested_actions' => $response['suggested_actions'],
            'processing_time_ms' => $response['processing_time_ms'],
        ]);
    }

    /**
     * Get size recommendation based on items
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recommendSize(Request $request): JsonResponse
    {
        $request->validate([
            'items_description' => 'required|string|max:500',
            'tenant_id' => 'required|integer',
            'site_id' => 'nullable|integer',
            'locale' => 'nullable|string|in:fr,en',
        ]);

        $this->chatbotService->setContext(
            $request->input('tenant_id'),
            $request->input('site_id'),
            $request->input('locale', 'fr')
        );

        $recommendation = $this->chatbotService->recommendBoxSize(
            $request->input('items_description')
        );

        return response()->json([
            'success' => true,
            'recommendation' => $recommendation,
        ]);
    }

    /**
     * Get conversation history
     *
     * @param Request $request
     * @param string $conversationId
     * @return JsonResponse
     */
    public function getConversation(Request $request, string $conversationId): JsonResponse
    {
        $messages = ChatMessage::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'intent' => $m->metadata['intent'] ?? null,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversationId,
            'messages' => $messages,
        ]);
    }

    /**
     * Get chatbot analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|integer',
            'period' => 'nullable|string|in:1d,7d,30d,90d',
        ]);

        $analytics = $this->chatbotService->getAnalytics(
            $request->input('tenant_id'),
            $request->input('period', '7d')
        );

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Request human handoff
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function requestHandoff(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|string|max:50',
            'tenant_id' => 'required|integer',
            'customer_name' => 'nullable|string|max:100',
            'customer_email' => 'nullable|email|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'reason' => 'nullable|string|max:500',
        ]);

        // In a real implementation, this would create a support ticket
        // or notify the support team

        // Store handoff request message
        $this->storeMessage($request->input('conversation_id'), 'system', 'Handoff requested to human agent', [
            'tenant_id' => $request->input('tenant_id'),
            'handoff' => true,
            'customer_name' => $request->input('customer_name'),
            'customer_email' => $request->input('customer_email'),
            'customer_phone' => $request->input('customer_phone'),
            'reason' => $request->input('reason'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Un conseiller va vous contacter dans les plus brefs délais.',
            'ticket_created' => true,
        ]);
    }

    /**
     * Store a chat message
     */
    protected function storeMessage(string $conversationId, string $role, string $content, array $metadata = []): void
    {
        try {
            ChatMessage::create([
                'conversation_id' => $conversationId,
                'role' => $role,
                'content' => $content,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            // If the table doesn't exist yet, just log and continue
            \Log::warning('Could not store chat message', ['error' => $e->getMessage()]);
        }
    }
}
