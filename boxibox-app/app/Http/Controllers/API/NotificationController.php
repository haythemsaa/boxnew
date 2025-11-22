<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PushNotificationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Register a new push notification token
     *
     * POST /api/notifications/register-token
     */
    public function registerToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:500',
            'platform' => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = $request->user();

        // Check if token already exists
        $existingToken = PushNotificationToken::where('token', $request->token)->first();

        if ($existingToken) {
            // Update existing token
            $existingToken->update([
                'customer_id' => $customer->id,
                'platform' => $request->platform,
                'device_name' => $request->device_name,
                'device_model' => $request->device_model,
                'app_version' => $request->app_version,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Token updated successfully',
                'data' => [
                    'token_id' => $existingToken->id,
                ]
            ]);
        }

        // Create new token
        $token = PushNotificationToken::create([
            'customer_id' => $customer->id,
            'token' => $request->token,
            'platform' => $request->platform,
            'device_name' => $request->device_name,
            'device_model' => $request->device_model,
            'app_version' => $request->app_version,
            'is_active' => true,
            'last_used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token registered successfully',
            'data' => [
                'token_id' => $token->id,
            ]
        ], 201);
    }

    /**
     * Unregister a push notification token
     *
     * POST /api/notifications/unregister-token
     */
    public function unregisterToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = $request->user();

        // Find and deactivate the token
        $token = PushNotificationToken::where('token', $request->token)
            ->where('customer_id', $customer->id)
            ->first();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found'
            ], 404);
        }

        $token->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'Token unregistered successfully'
        ]);
    }

    /**
     * Get all registered tokens for the authenticated customer
     *
     * GET /api/notifications/tokens
     */
    public function getTokens(Request $request): JsonResponse
    {
        $customer = $request->user();

        $tokens = PushNotificationToken::where('customer_id', $customer->id)
            ->active()
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'platform' => $token->platform,
                    'device_name' => $token->device_name,
                    'device_model' => $token->device_model,
                    'app_version' => $token->app_version,
                    'last_used_at' => $token->last_used_at?->format('Y-m-d H:i:s'),
                    'created_at' => $token->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'tokens' => $tokens,
                'total' => $tokens->count(),
            ]
        ]);
    }

    /**
     * Update notification preferences
     *
     * PUT /api/notifications/preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_reminders' => 'nullable|boolean',
            'contract_updates' => 'nullable|boolean',
            'promotions' => 'nullable|boolean',
            'system_notifications' => 'nullable|boolean',
            'maintenance_alerts' => 'nullable|boolean',
            'chat_messages' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customer = $request->user();

        // Get current preferences or set defaults
        $currentPreferences = $customer->notification_preferences ?? [
            'payment_reminders' => true,
            'contract_updates' => true,
            'promotions' => true,
            'system_notifications' => true,
            'maintenance_alerts' => true,
            'chat_messages' => true,
        ];

        // Update only provided preferences
        $updatedPreferences = array_merge($currentPreferences, array_filter($request->only([
            'payment_reminders',
            'contract_updates',
            'promotions',
            'system_notifications',
            'maintenance_alerts',
            'chat_messages',
        ]), function ($value) {
            return $value !== null;
        }));

        $customer->update([
            'notification_preferences' => $updatedPreferences
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
            'data' => [
                'preferences' => $updatedPreferences
            ]
        ]);
    }

    /**
     * Get notification preferences
     *
     * GET /api/notifications/preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $customer = $request->user();

        $preferences = $customer->notification_preferences ?? [
            'payment_reminders' => true,
            'contract_updates' => true,
            'promotions' => true,
            'system_notifications' => true,
            'maintenance_alerts' => true,
            'chat_messages' => true,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'preferences' => $preferences
            ]
        ]);
    }
}
