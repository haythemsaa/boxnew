<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ChatbotService;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Process chatbot message
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
        ]);

        $tenantId = $request->user()?->tenant_id ?? $request->get('tenant_id');

        $response = $this->chatbotService->chat(
            $request->input('message'),
            $request->input('conversation_id'),
            $tenantId
        );

        return response()->json($response);
    }

    /**
     * Get box size recommendation
     */
    public function recommendSize(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
        ]);

        $recommendation = $this->chatbotService->recommendBoxSize($request->input('items'));

        return response()->json($recommendation);
    }
}
