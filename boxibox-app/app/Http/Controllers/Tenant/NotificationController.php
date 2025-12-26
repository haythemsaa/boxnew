<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\NotificationLog;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\AlertNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display notification settings and logs
     */
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        // Get notification preferences
        $preferences = NotificationPreference::where('tenant_id', $tenant->id)
            ->first() ?? new NotificationPreference([
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => false,
            ]);

        // Get recent notification logs
        $logs = NotificationLog::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'type' => $log->type,
                'channel' => $log->channel,
                'recipient' => $log->recipient,
                'subject' => $log->subject,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at->toIso8601String(),
                'sent_at' => $log->sent_at?->toIso8601String(),
            ]);

        // Stats
        $stats = [
            'total_sent' => NotificationLog::where('tenant_id', $tenant->id)
                ->where('status', 'sent')
                ->count(),
            'sent_today' => NotificationLog::where('tenant_id', $tenant->id)
                ->where('status', 'sent')
                ->whereDate('created_at', today())
                ->count(),
            'failed' => NotificationLog::where('tenant_id', $tenant->id)
                ->where('status', 'failed')
                ->count(),
            'pending' => NotificationLog::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->count(),
        ];

        // Notification types configuration
        $notificationTypes = [
            [
                'id' => 'invoice_created',
                'name' => 'Nouvelle facture',
                'description' => 'Notification envoyée quand une facture est créée',
                'email' => $preferences->invoice_created_email ?? true,
                'sms' => $preferences->invoice_created_sms ?? false,
                'push' => $preferences->invoice_created_push ?? false,
            ],
            [
                'id' => 'payment_received',
                'name' => 'Paiement reçu',
                'description' => 'Confirmation de réception de paiement',
                'email' => $preferences->payment_received_email ?? true,
                'sms' => $preferences->payment_received_sms ?? false,
                'push' => $preferences->payment_received_push ?? false,
            ],
            [
                'id' => 'payment_reminder',
                'name' => 'Rappel de paiement',
                'description' => 'Rappel pour les factures impayées',
                'email' => $preferences->payment_reminder_email ?? true,
                'sms' => $preferences->payment_reminder_sms ?? false,
                'push' => $preferences->payment_reminder_push ?? false,
            ],
            [
                'id' => 'contract_expiring',
                'name' => 'Expiration de contrat',
                'description' => 'Alerte avant l\'expiration d\'un contrat',
                'email' => $preferences->contract_expiring_email ?? true,
                'sms' => $preferences->contract_expiring_sms ?? false,
                'push' => $preferences->contract_expiring_push ?? false,
            ],
            [
                'id' => 'access_alert',
                'name' => 'Alerte d\'accès',
                'description' => 'Notification lors d\'un accès suspect',
                'email' => $preferences->access_alert_email ?? true,
                'sms' => $preferences->access_alert_sms ?? true,
                'push' => $preferences->access_alert_push ?? true,
            ],
            [
                'id' => 'iot_alert',
                'name' => 'Alerte IoT',
                'description' => 'Alerte capteur (température, humidité, etc.)',
                'email' => $preferences->iot_alert_email ?? true,
                'sms' => $preferences->iot_alert_sms ?? false,
                'push' => $preferences->iot_alert_push ?? true,
            ],
            [
                'id' => 'booking_confirmed',
                'name' => 'Réservation confirmée',
                'description' => 'Confirmation d\'une nouvelle réservation',
                'email' => $preferences->booking_confirmed_email ?? true,
                'sms' => $preferences->booking_confirmed_sms ?? false,
                'push' => $preferences->booking_confirmed_push ?? false,
            ],
            [
                'id' => 'welcome',
                'name' => 'Bienvenue client',
                'description' => 'Email de bienvenue pour les nouveaux clients',
                'email' => $preferences->welcome_email ?? true,
                'sms' => $preferences->welcome_sms ?? false,
                'push' => $preferences->welcome_push ?? false,
            ],
        ];

        return Inertia::render('Tenant/Notifications/Index', [
            'preferences' => $preferences,
            'notificationTypes' => $notificationTypes,
            'logs' => $logs,
            'stats' => $stats,
            'channels' => [
                'email' => [
                    'enabled' => $preferences->email_enabled ?? true,
                    'configured' => !empty(config('mail.from.address')),
                ],
                'sms' => [
                    'enabled' => $preferences->sms_enabled ?? false,
                    'configured' => !empty(config('services.twilio.sid')),
                ],
                'push' => [
                    'enabled' => $preferences->push_enabled ?? false,
                    'configured' => !empty(config('services.firebase.server_key')),
                ],
            ],
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'types' => 'array',
        ]);

        $preferences = NotificationPreference::firstOrCreate(
            ['tenant_id' => $tenant->id],
            []
        );

        $preferences->email_enabled = $validated['email_enabled'] ?? true;
        $preferences->sms_enabled = $validated['sms_enabled'] ?? false;
        $preferences->push_enabled = $validated['push_enabled'] ?? false;

        // Update individual type preferences
        if (isset($validated['types'])) {
            foreach ($validated['types'] as $typeId => $channels) {
                $preferences->{$typeId . '_email'} = $channels['email'] ?? false;
                $preferences->{$typeId . '_sms'} = $channels['sms'] ?? false;
                $preferences->{$typeId . '_push'} = $channels['push'] ?? false;
            }
        }

        $preferences->save();

        return back()->with('success', 'Préférences de notification mises à jour');
    }

    /**
     * Configure email settings
     */
    public function emailSettings(Request $request)
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Tenant/Notifications/EmailSettings', [
            'settings' => [
                'from_name' => $tenant->notification_from_name ?? config('mail.from.name'),
                'from_email' => $tenant->notification_from_email ?? config('mail.from.address'),
                'reply_to' => $tenant->notification_reply_to ?? '',
                'signature' => $tenant->email_signature ?? '',
                'footer_text' => $tenant->email_footer ?? '',
            ],
        ]);
    }

    /**
     * Update email settings
     */
    public function updateEmailSettings(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'signature' => 'nullable|string|max:1000',
            'footer_text' => 'nullable|string|max:500',
        ]);

        $tenant->update([
            'notification_from_name' => $validated['from_name'],
            'notification_from_email' => $validated['from_email'],
            'notification_reply_to' => $validated['reply_to'],
            'email_signature' => $validated['signature'],
            'email_footer' => $validated['footer_text'],
        ]);

        return back()->with('success', 'Paramètres email mis à jour');
    }

    /**
     * Configure SMS settings (Twilio)
     */
    public function smsSettings(Request $request)
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Tenant/Notifications/SmsSettings', [
            'settings' => [
                'provider' => $tenant->sms_provider ?? 'twilio',
                'twilio_sid' => $tenant->twilio_sid ? '***' . substr($tenant->twilio_sid, -4) : '',
                'twilio_token' => $tenant->twilio_token ? '********' : '',
                'twilio_from' => $tenant->twilio_from ?? '',
                'vonage_key' => $tenant->vonage_key ? '***' . substr($tenant->vonage_key, -4) : '',
                'vonage_secret' => $tenant->vonage_secret ? '********' : '',
                'vonage_from' => $tenant->vonage_from ?? '',
            ],
            'configured' => !empty($tenant->twilio_sid) || !empty($tenant->vonage_key),
        ]);
    }

    /**
     * Update SMS settings
     */
    public function updateSmsSettings(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'provider' => 'required|in:twilio,vonage',
            'twilio_sid' => 'nullable|string',
            'twilio_token' => 'nullable|string',
            'twilio_from' => 'nullable|string|max:20',
            'vonage_key' => 'nullable|string',
            'vonage_secret' => 'nullable|string',
            'vonage_from' => 'nullable|string|max:20',
        ]);

        $updateData = ['sms_provider' => $validated['provider']];

        // Only update secrets if new values provided (not masked)
        if ($validated['twilio_sid'] && !str_starts_with($validated['twilio_sid'], '***')) {
            $updateData['twilio_sid'] = $validated['twilio_sid'];
        }
        if ($validated['twilio_token'] && $validated['twilio_token'] !== '********') {
            $updateData['twilio_token'] = encrypt($validated['twilio_token']);
        }
        if (!empty($validated['twilio_from'])) {
            $updateData['twilio_from'] = $validated['twilio_from'];
        }
        if ($validated['vonage_key'] && !str_starts_with($validated['vonage_key'], '***')) {
            $updateData['vonage_key'] = $validated['vonage_key'];
        }
        if ($validated['vonage_secret'] && $validated['vonage_secret'] !== '********') {
            $updateData['vonage_secret'] = encrypt($validated['vonage_secret']);
        }
        if (!empty($validated['vonage_from'])) {
            $updateData['vonage_from'] = $validated['vonage_from'];
        }

        $tenant->update($updateData);

        return back()->with('success', 'Paramètres SMS mis à jour');
    }

    /**
     * Configure push notification settings (Firebase)
     */
    public function pushSettings(Request $request)
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('Tenant/Notifications/PushSettings', [
            'settings' => [
                'firebase_project_id' => $tenant->firebase_project_id ?? '',
                'firebase_server_key' => $tenant->firebase_server_key ? '***...***' : '',
                'vapid_public_key' => $tenant->vapid_public_key ?? '',
            ],
            'configured' => !empty($tenant->firebase_server_key),
        ]);
    }

    /**
     * Update push notification settings
     */
    public function updatePushSettings(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'firebase_project_id' => 'nullable|string|max:255',
            'firebase_server_key' => 'nullable|string',
            'vapid_public_key' => 'nullable|string',
            'vapid_private_key' => 'nullable|string',
        ]);

        $updateData = [];

        if (!empty($validated['firebase_project_id'])) {
            $updateData['firebase_project_id'] = $validated['firebase_project_id'];
        }
        if (!empty($validated['firebase_server_key']) && $validated['firebase_server_key'] !== '***...***') {
            $updateData['firebase_server_key'] = encrypt($validated['firebase_server_key']);
        }
        if (!empty($validated['vapid_public_key'])) {
            $updateData['vapid_public_key'] = $validated['vapid_public_key'];
        }
        if (!empty($validated['vapid_private_key'])) {
            $updateData['vapid_private_key'] = encrypt($validated['vapid_private_key']);
        }

        if (!empty($updateData)) {
            $tenant->update($updateData);
        }

        return back()->with('success', 'Paramètres push mis à jour');
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $validated = $request->validate([
            'channel' => 'required|in:email,sms,push',
            'recipient' => 'required|string',
        ]);

        $tenant = $request->user()->tenant;

        try {
            switch ($validated['channel']) {
                case 'email':
                    \Illuminate\Support\Facades\Mail::raw(
                        "Ceci est un test de notification email de {$tenant->name}.\n\nSi vous recevez ce message, votre configuration email fonctionne correctement.",
                        function ($message) use ($validated, $tenant) {
                            $message->to($validated['recipient'])
                                ->subject('Test de notification - ' . $tenant->name);
                        }
                    );
                    break;

                case 'sms':
                    $this->notificationService->sendSms(
                        $validated['recipient'],
                        "Test de notification SMS de {$tenant->name}. Si vous recevez ce message, votre configuration SMS fonctionne correctement."
                    );
                    break;

                case 'push':
                    // TODO: Implement push notification test
                    break;
            }

            // Log the test
            NotificationLog::create([
                'tenant_id' => $tenant->id,
                'type' => 'test',
                'channel' => $validated['channel'],
                'recipient' => $validated['recipient'],
                'subject' => 'Test notification',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return back()->with('success', 'Notification de test envoyée');
        } catch (\Exception $e) {
            NotificationLog::create([
                'tenant_id' => $tenant->id,
                'type' => 'test',
                'channel' => $validated['channel'],
                'recipient' => $validated['recipient'],
                'subject' => 'Test notification',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * View notification logs
     */
    public function logs(Request $request)
    {
        $tenant = $request->user()->tenant;
        $channel = $request->input('channel');
        $status = $request->input('status');
        $from = $request->input('from');
        $to = $request->input('to');

        $logs = NotificationLog::where('tenant_id', $tenant->id)
            ->when($channel, fn($q) => $q->where('channel', $channel))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('Tenant/Notifications/Logs', [
            'logs' => $logs,
            'filters' => [
                'channel' => $channel,
                'status' => $status,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | API Methods for NotificationCenter Component
    |--------------------------------------------------------------------------
    */

    /**
     * Get notifications for the current user (API)
     */
    public function apiList(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->tenant_id;

        // Generate fresh alerts for the tenant
        $alertService = new AlertNotificationService();
        $alertService->generateAlertsForTenant($tenantId);

        // Get notifications for this user
        $notifications = Notification::where('tenant_id', $tenantId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id'); // Tenant-wide notifications
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority ?? 'medium',
                    'is_read' => (bool) $notification->read_at,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at->toIso8601String(),
                    'related_type' => $notification->related_type,
                    'related_id' => $notification->related_id,
                    'data' => $notification->data,
                ];
            });

        $unreadCount = Notification::where('tenant_id', $tenantId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read (API)
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification non trouvée'], 404);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read (API)
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        Notification::where('tenant_id', $user->tenant_id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->whereNull('read_at')
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get unread notification count (API)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = Notification::where('tenant_id', $user->tenant_id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Delete a notification (API)
     */
    public function deleteNotification(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification non trouvée'], 404);
        }

        $notification->delete();

        return response()->json(['success' => true]);
    }
}
