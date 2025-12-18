<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\PushNotificationService;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PushNotificationController extends Controller
{
    protected PushNotificationService $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * Get VAPID public key for client subscription.
     */
    public function getPublicKey(): JsonResponse
    {
        return response()->json([
            'publicKey' => $this->pushService->getPublicKey(),
        ]);
    }

    /**
     * Subscribe to push notifications.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
            'deviceType' => 'nullable|string|in:web,mobile,tablet',
            'browser' => 'nullable|string|max:50',
            'os' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifie',
            ], 401);
        }

        $subscription = $this->pushService->subscribe($user, $request->only([
            'endpoint', 'keys', 'contentEncoding', 'deviceType', 'browser', 'os'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Abonnement aux notifications active',
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Unsubscribe from push notifications.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $success = $this->pushService->unsubscribe($request->endpoint);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Desabonnement effectue' : 'Abonnement non trouve',
        ]);
    }

    /**
     * Get user's push subscriptions.
     */
    public function getSubscriptions(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = PushSubscription::where('user_id', $user->id)
            ->active()
            ->get()
            ->map(function ($sub) {
                return [
                    'id' => $sub->id,
                    'device_type' => $sub->device_type,
                    'browser' => $sub->browser,
                    'os' => $sub->os,
                    'last_used_at' => $sub->last_used_at?->diffForHumans(),
                    'created_at' => $sub->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Send test notification to current user.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $this->pushService->sendToUser($user, [
            'type' => 'system',
            'title' => 'Test de notification',
            'body' => 'Les notifications push fonctionnent correctement !',
            'icon' => '/images/icons/icon-192x192.png',
            'url' => '/tenant/dashboard',
        ]);

        return response()->json([
            'success' => $count > 0,
            'message' => $count > 0 ? 'Notification envoyee' : 'Aucun appareil inscrit',
            'devices_notified' => $count,
        ]);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'payment_reminders' => 'nullable|boolean',
            'contract_alerts' => 'nullable|boolean',
            'booking_notifications' => 'nullable|boolean',
            'iot_alerts' => 'nullable|boolean',
            'marketing' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Store preferences in user's notification_preferences column or separate table
        $preferences = $user->notification_preferences ?? [];
        $preferences['push'] = array_merge($preferences['push'] ?? [], $request->only([
            'payment_reminders',
            'contract_alerts',
            'booking_notifications',
            'iot_alerts',
            'marketing',
        ]));

        $user->update(['notification_preferences' => $preferences]);

        return response()->json([
            'success' => true,
            'message' => 'Preferences mises a jour',
            'preferences' => $preferences['push'],
        ]);
    }

    /**
     * Revoke a specific subscription.
     */
    public function revokeSubscription(Request $request, int $subscriptionId): JsonResponse
    {
        $user = $request->user();

        $subscription = PushSubscription::where('id', $subscriptionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement non trouve',
            ], 404);
        }

        $subscription->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'Appareil deconnecte',
        ]);
    }
}
